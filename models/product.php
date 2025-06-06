<?php

class Product {
    public int $id;
    public string $name;
    public string $description;
    public string $brand;
    public float $price;
    public int $stock;
    public ?string $imageUrl;
    public ?int $categoryId;
    public ?int $discountId;

    public function __construct(array $data) {
        $this->id = (int)$data['id'];
        $this->name = $data['name'];
        $this->brand = $data['brand'];
        $this->description = $data['description'] ?? '';
        $this->price = (float)$data['price'];
        $this->stock = (int)$data['stock'];
        $this->imageUrl = $data['image_url'] ?? null;
        $this->categoryId = $data['category_id'] ?? null;
        $this->discountId = $data['discount_id'] ?? null;
    }

    public function getFormattedPrice(): string {
        return number_format($this->price, 2) . " zł";
    }
}

?>