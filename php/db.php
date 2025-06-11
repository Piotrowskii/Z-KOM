<?php

require_once __DIR__ . '/../viewModels/productViewModel.php';
require_once __DIR__ . '/../viewModels/storeReviewViewModel.php';
require_once __DIR__ . '/../viewModels/reviewViewModel.php';
require_once __DIR__ . '/../viewModels/userModel.php';
require_once __DIR__ . '/../viewModels/orderModel.php';
require_once __DIR__ . '/../viewModels/addressModel.php';
require_once __DIR__ . '/../viewModels/orderItemViewModel.php';
require_once __DIR__ . '/../viewModels/discountModel.php';
require_once __DIR__ . '/../viewModels/categoryModel.php';
require_once __DIR__ . '/../viewModels/attributeModel.php';

class Db {
    private $conn;

    public function __construct() {
        $host = 'localhost';
        $dbname = 'sklep';
        $user = 'postgres';
        $password = 'postgres';
        $port = '5432';

        $this->conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
        if (!$this->conn) {
            die("Błąd połączenia z bazą danych.");
        }
    }


    // Zarządzanie użytkownikami
    public function loginUser($email, $password): bool {

        $query = "SELECT id, password_hash, permission_id FROM Users WHERE email = $1";
        $result = pg_query_params($this->conn, $query, [$email]);

        if ($result && pg_num_rows($result) === 1) {
            $user = pg_fetch_assoc($result);
            
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['permission_id'] = $user['permission_id'];
                $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];

                return true;
            }
        }

        return false;
    }

    public function addUser($email, $passwordHash, $name, $surname, $phone, $permissionId = 1, $addressId = null): bool {
        $query = "INSERT INTO users (email, password_hash, name, surname, phone, permission_id, address_id)VALUES ($1, $2, $3, $4, $5, $6, $7)";
        $result = pg_query_params($this->conn, $query, [$email, $passwordHash, $name, $surname, $phone, $permissionId, $addressId]);

        return $result !== false;
    }


    public function userExistsByEmail(string $email): bool {
        $query = "SELECT 1 FROM users WHERE email = $1";
        $result = pg_query_params($this->conn, $query, [$email]);

        return $result && pg_num_rows($result) > 0;
    }

    public function userExistsById(int $id): bool {
        $query = "SELECT 1 FROM Users WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$id]);
        return $result && pg_num_rows($result) > 0;
    }

    public function getUserById(int $id): ?User {
        $query = "SELECT * FROM Users WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$id]);

        if ($result && pg_num_rows($result) === 1) {
            $row = pg_fetch_assoc($result);
            return new User($row);
        }

        return null;
    }

    public function getAddressById(int $addressId): ?Address {
        $query = "SELECT * FROM Addresses WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$addressId]);

        if ($result && pg_num_rows($result) === 1) {
            $row = pg_fetch_assoc($result);
            return new Address($row);
        }

        return null;
    }

    public function upsertUserAddress(int $userId, string $street, string $houseNumber, string $city, string $postalCode, string $country): bool {
        $query = "CALL upsert_user_address($1, $2, $3, $4, $5, $6)";
        $result = pg_query_params($this->conn, $query, [$userId, $street, $houseNumber, $city, $postalCode, $country]);

        return $result !== false;
    }

    //Zarządzanie zamówieniami
    public function getAllProductsFromOrder(int $orderId): array {
        $query = "SELECT * FROM order_item_view WHERE order_id = $1";
        $result = pg_query_params($this->conn, $query, [$orderId]);

        $orderItems = [];

        if($result){
            while ($row = pg_fetch_assoc($result)) {
                $orderItems[] = new OrderItemViewModel($row);
            }
        }

        return $orderItems;
    }

    public function insertOrder(int $userId, float $total, array $orderItems): ?int {
        try {
            pg_query($this->conn, 'BEGIN');

            $orderQuery = "INSERT INTO orders (user_id, total) VALUES ($1, $2) RETURNING id";
            $orderResult = pg_query_params($this->conn, $orderQuery, [$userId, $total]);

            if (!$orderResult || pg_num_rows($orderResult) !== 1) {
                throw new Exception("Nie udało się utworzyć rekordu zamówienia.");
            }

            $orderRow = pg_fetch_assoc($orderResult);
            $orderId = $orderRow['id'];

            foreach ($orderItems as $item) {
                $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ($1, $2, $3, $4)";
                $res1 = pg_query_params($this->conn, $itemQuery, [$orderId, $item['product_id'], $item['qty'], $item['price']]);

                $updateStockQuery = "UPDATE products SET stock = stock - $1 WHERE id = $2";
                $res2 = pg_query_params($this->conn, $updateStockQuery, [$item['qty'], $item['product_id']]);

                if (!$res1 || !$res2) {
                    throw new Exception("Błąd podczas zapisywania pozycji zamówienia lub aktualizacji stanu magazynowego.");
                }
            }

            pg_query($this->conn, 'COMMIT');
            return $orderId;
        } catch (Exception $e) {
            pg_query($this->conn, 'ROLLBACK');
            return null;
        }
    }

    public function doesOrderBelongsToUser(int $orderId, int $userId): bool {
        $query = "SELECT 1 FROM orders WHERE id = $1 AND user_id = $2";
        $result = pg_query_params($this->conn, $query, [$orderId, $userId]);
        return $result && pg_num_rows($result) > 0;
    }

    public function getOrderById(int $orderId): ?Order {
        $query = "SELECT * FROM orders WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$orderId]);

        if ($result && pg_num_rows($result) === 1) {
            $data = pg_fetch_assoc($result);
            return new Order($data);
        }
        return null;
    }

    public function getOrdersByUserId(int $userId): array {
        $query = "SELECT * FROM orders WHERE user_id = $1 ORDER BY created_at DESC";
        $result = pg_query_params($this->conn, $query, [$userId]);

        $orders = [];

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $orders[] = new Order($row);
            }
        }

        return $orders;
    }

    //Zarządzanie kategoriami
    public function getAllCategories(): array {
        $query = "SELECT * FROM categories ORDER BY name ASC";
        $result = pg_query($this->conn, $query);

        $categories = [];

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $categories[] = new Category($row);
            }
        }

        return $categories;
    }

    public function addCategory(string $name): bool {
        $query = "INSERT INTO categories (name) VALUES ($1)";
        $result = pg_query_params($this->conn, $query, [$name]);

        return $result !== false;
    }

    public function updateCategory(int $categoryId, string $name): bool {
        $query = "UPDATE categories SET name = $1 WHERE id = $2";
        $result = pg_query_params($this->conn, $query, [$name, $categoryId]);

        return $result !== false;
    }

    public function deleteCategory(int $categoryId): bool {
        $query = "DELETE FROM categories WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$categoryId]);

        return $result !== false;
    }

    public function getCategoryById($id): ?Category {
        $query = "SELECT * FROM categories WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$id]);

        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            return new Category($row);
        }

        return null; 
    }

    //Zarządzania opiniami
    public function getProductReviewsByComment(string $input): array {
        $query = "SELECT * FROM reviews_view WHERE comment ILIKE $1";
        $result = pg_query_params($this->conn, $query, ['%'.$input.'%']);

        $reviews = [];
        while ($row = pg_fetch_assoc($result)) {
            $reviews[] = new ReviewView($row);
        }
        return $reviews;
    }

    public function getStoreReviewsByComment(string $input): array {
        $query = "SELECT * FROM store_reviews_view WHERE comment ILIKE $1";
        $result = pg_query_params($this->conn, $query, ['%'.$input.'%']);

        $reviews = [];
        while ($row = pg_fetch_assoc($result)) {
            $reviews[] = new StoreReviewViewModel($row); 
        }
        return $reviews;
    }

    public function deleteProductReviewById(int $reviewId): bool {
        $query = "DELETE FROM product_reviews WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$reviewId]);

        return $result !== false;
    }

    public function deleteStoreReviewById(int $reviewId): bool {
        $query = "DELETE FROM store_reviews WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$reviewId]);

        return $result !== false;
    }


    //Zarządzania atrybutami
    public function getAllAttributes(): array {
        $query = "SELECT * FROM attributes ORDER BY name ASC";
        $result = pg_query($this->conn, $query);

        $attributes = [];

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $attributes[] = new DbAttribute($row);
            }
        }

        return $attributes;
    }

    public function addAttribute(string $name, ?string $unit): bool {
        $query = "INSERT INTO attributes (name, unit) VALUES ($1, $2)";
        $result = pg_query_params($this->conn, $query, [$name, $unit]);

        return $result !== false;
    }

    public function updateAttribute(int $attributeId, string $name, ?string $unit): bool {
        $query = "UPDATE attributes SET name = $1, unit = $2 WHERE id = $3";
        $result = pg_query_params($this->conn, $query, [$name, $unit, $attributeId]);

        return $result !== false;
    }

    public function deleteAttribute(int $attributeId): bool {
        $query = "DELETE FROM attributes WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$attributeId]);

        return $result !== false;
    }


    //Zarządzanie produktami
    public function getAllProductsFromCategory($categoryId,$orderBy): array{
        $query = "SELECT * FROM product_view WHERE category_id = $1 {$orderBy}";
        $result = pg_query_params($this->conn, $query, [$categoryId]);

        $products = [];

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $products[] = new ProductViewModel($row);
            }
        }

        return $products;
    }

    public function getProductById($id): ?ProductViewModel {
        $query = "SELECT * FROM product_view WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$id]);

        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            return new ProductViewModel($row);
        }

        return null; 
    }

    public function doesProductExists(int $productId): bool {
        $query = "SELECT 1 FROM products WHERE id = $1 LIMIT 1";
        $result = pg_query_params($this->conn, $query, [$productId]);

        return $result && pg_num_rows($result) > 0;
    }


    public function deleteProductById(int $id): bool {
        $query = "DELETE FROM products WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$id]);

        return $result !== false && pg_affected_rows($result) > 0;
    }

    public function editProduct(int $id, string $name, string $brand, ?string $description, float $price, int $stock, string $imageUrl, ?int $categoryId, ?int $discountId): bool {
        $query = "UPDATE products SET name = $1, brand = $2, description = $3, price = $4, stock = $5, image_url = $6, category_id = $7, discount_id = $8 WHERE id = $9";
        $result = pg_query_params($this->conn, $query, [$name, $brand, $description, $price, $stock, $imageUrl, $categoryId, $discountId, $id]);

        return $result !== false;
    }   

    public function addProduct(string $name, string $brand, ?string $description, float $price, int $stock, string $imageUrl, ?int $categoryId, ?int $discountId): ?int {
        $query = "INSERT INTO products (name, brand, description, price, stock, image_url, category_id, discount_id) VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING id";
        $result = pg_query_params($this->conn, $query, [$name, $brand, $description, $price, $stock, $imageUrl, $categoryId, $discountId]);

        if ($result && pg_num_rows($result) === 1) {
            $row = pg_fetch_assoc($result);
            return (int) $row['id'];
        }

        return null;
    }


    public function getProductsBySearch($searchTerm,$orderBy): array {
        $query = "SELECT * FROM product_view WHERE LOWER(name) LIKE $1 OR LOWER(brand) LIKE $1 {$orderBy} LIMIT 30";

        $param = '%' . strtolower($searchTerm) . '%';
        $result = pg_query_params($this->conn, $query, [$param]);

        $products = [];
        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $products[] = new ProductViewModel($row);
            }
        }

        return $products;
    }

    public function getProductsAttributes($productId): array {
        $query = "SELECT product_attributes.id, attributes.name, product_attributes.value, attributes.unit FROM product_attributes JOIN attributes ON attributes.id = product_attributes.attribute_id WHERE product_attributes.product_id = $1";
        $result = pg_query_params($this->conn, $query, [$productId]);

        $attributes = [];

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $attributes[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'value' => $row['value'],
                    'unit' => $row['unit']
                ];
            }
        }

        return $attributes;
    }

    public function updateProductAttribute(int $attributeId, string $value): bool {
        $query = "UPDATE product_attributes SET value = $1 WHERE id = $2";
        $result = pg_query_params($this->conn, $query, [$value, $attributeId]);

        return $result !== false;
    }

    public function addProductAttribute(int $productId, int $attributeId, string $value): bool
    {
        $query = "INSERT INTO product_attributes (product_id, attribute_id, value) VALUES ($1, $2, $3)";
        $result = pg_query_params($this->conn, $query, [$productId, $attributeId, $value]);
        return $result !== false;
    }


    public function deleteProductAttribute(int $attributeId): bool {
        $query = "DELETE FROM product_attributes WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$attributeId]);

        return $result !== false;
    }

    public function getProductMissingAttributes(int $productId): array {
        $query = "SELECT attributes.id, attributes.name, attributes.unit FROM attributes LEFT JOIN product_attributes ON product_attributes.attribute_id = attributes.id AND product_attributes.product_id = $1 WHERE product_attributes.attribute_id IS NULL";
        $result = pg_query_params($this->conn, $query, [$productId]);
        
        $missingAttributes = [];
        
        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $missingAttributes[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'unit' => $row['unit']
                ];
            }
        }
        
        return $missingAttributes;
    }

    //Zarządzanie recenzjami
    public function getReviewsByProductId($productId): array {
        $query = "SELECT * FROM reviews_view WHERE product_id = $1 ORDER BY created_at DESC";
        $result = pg_query_params($this->conn, $query, [$productId]);

        $reviews = [];

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $reviews[] = new ReviewView($row);
            }
        }

        return $reviews;
    }   

    public function getTop3StoreReviews(): array {
        $query = "SELECT * FROM store_reviews_view ORDER BY rating DESC LIMIT 3";
        $result = pg_query($this->conn, $query);

        $reviews = [];

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $reviews[] = new StoreReviewViewModel($row);
            }
        }

        return $reviews;
    }

    public function addReview($userId, $productId, $rating, $comment): void {
        $query = "INSERT INTO product_reviews (user_id, product_id, rating, comment) VALUES ($1, $2, $3, $4)";
        pg_query_params($this->conn, $query, [$userId, $productId, $rating, $comment]);
    }  

    public function hasUserReviewedProduct($userId, $productId): bool {
        $query = "SELECT 1 FROM product_reviews WHERE user_id = $1 AND product_id = $2 LIMIT 1";
        $result = pg_query_params($this->conn, $query, [$userId, $productId]);

        return $result && pg_num_rows($result) > 0;
    }



    public function getAllDiscounts(): array {
        $query = "SELECT * FROM discounts ORDER BY start_date DESC";
        $result = pg_query($this->conn, $query);

        $discounts = [];

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $discounts[] = new Discount($row);
            }
        }

        return $discounts;
    }

    


    



    



    public function __destruct() {
        pg_close($this->conn);
    }
}
