CREATE OR REPLACE FUNCTION generate_fake_tracking_id()
RETURNS TEXT AS $$
DECLARE
    letters TEXT := 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    result TEXT := '';
    i INT;
BEGIN
    FOR i IN 1..2 LOOP
        result := result || substr(letters, floor(random() * 26 + 1)::int, 1);
    END LOOP;

    FOR i IN 1..9 LOOP
        result := result || floor(random() * 10)::int;
    END LOOP;

    FOR i IN 1..2 LOOP
        result := result || substr(letters, floor(random() * 26 + 1)::int, 1);
    END LOOP;

    RETURN result;
END;
$$ LANGUAGE plpgsql;

CREATE TABLE permissions (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL
);

CREATE TABLE addresses (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    street_name VARCHAR(255) NOT NULL,
    house_number VARCHAR(10) NOT NULL,
    city VARCHAR(255) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(255) NOT NULL
);

CREATE TABLE users (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    permission_id INT NOT NULL,
    address_id INT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    surname VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (permission_id) REFERENCES permissions(id),
    FOREIGN KEY (address_id) REFERENCES addresses(id)
);

CREATE TABLE discounts (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    discount_percent DECIMAL(5,2) NOT NULL CHECK (discount_percent >= 0 AND discount_percent <= 100),
    name TEXT NOT NULL,
    start_date DATE NOT NULL DEFAULT CURRENT_DATE,
    end_date DATE NOT NULL DEFAULT (CURRENT_DATE + INTERVAL '7 days'),
    active BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE categories (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL
);

CREATE TABLE products (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    brand VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    image_url VARCHAR NOT NULL,
    category_id INT,
    discount_id INT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (discount_id) REFERENCES discounts(id) ON DELETE SET NULL
);

CREATE TABLE attributes (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    unit VARCHAR(50)
);

CREATE TABLE product_attributes (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    product_id INT NOT NULL,
    attribute_id INT NOT NULL,
    value TEXT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    tracking_id TEXT DEFAULT generate_fake_tracking_id(),
    status VARCHAR(20) DEFAULT 'nowe',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

CREATE TABLE product_reviews (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment VARCHAR(1024) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE store_reviews (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment VARCHAR(1024) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE VIEW store_reviews_view AS
SELECT store_reviews.id, store_reviews.user_id, store_reviews.rating, store_reviews.comment, store_reviews.created_at, users.name, users.surname
FROM store_reviews JOIN users ON store_reviews.user_id = users.id;

CREATE VIEW reviews_view AS
SELECT product_reviews.id, product_reviews.user_id, product_reviews.product_id, product_reviews.rating, product_reviews.comment, product_reviews.created_at, users.name, users.surname
FROM product_reviews JOIN users ON product_reviews.user_id = users.id;

CREATE OR REPLACE VIEW product_view AS
SELECT products.*, (SELECT COALESCE(ROUND(AVG(product_reviews.rating), 2), 0) FROM product_reviews WHERE product_reviews.product_id = products.id) AS rating, discounts.discount_percent,ROUND(products.price * (1 - COALESCE(discounts.discount_percent, 0) / 100), 2) AS final_price
FROM products LEFT JOIN discounts ON discounts.id = products.discount_id AND discounts.active = TRUE AND CURRENT_DATE BETWEEN discounts.start_date AND discounts.end_date;

CREATE OR REPLACE VIEW order_item_view AS
SELECT products.name, order_items.quantity, order_items.price, products.image_url, order_items.order_id, order_items.product_id AS id
FROM order_items LEFT JOIN products ON products.id = order_items.product_id;


CREATE OR REPLACE PROCEDURE upsert_user_address(
    v_user_id INT,
    v_street TEXT,
    v_house_number TEXT,
    v_city TEXT,
    v_postal_code TEXT,
    v_country TEXT
)
LANGUAGE plpgsql
AS $$
DECLARE
    addr_id INT;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM users WHERE id = v_user_id) THEN
        RAISE EXCEPTION 'User does not exist';
    END IF;

    SELECT address_id INTO addr_id FROM users WHERE id = v_user_id;

    IF addr_id IS NOT NULL THEN
        UPDATE addresses SET street_name = v_street, house_number = v_house_number, city = v_city, postal_code = v_postal_code, country = v_country WHERE id = addr_id;
    ELSE
        INSERT INTO addresses (street_name, house_number, city, postal_code, country)
        VALUES (v_street, v_house_number, v_city, v_postal_code, v_country) RETURNING id INTO addr_id;

        UPDATE users SET address_id = addr_id WHERE id = v_user_id;
    END IF;
END;
$$;



-- Permisje
INSERT INTO permissions (name) VALUES 
('user'),
('admin');

-- Dodanie adresów 
INSERT INTO addresses (street_name, house_number, city, postal_code, country) VALUES
('Kwiatowa', '10', 'Warsaw', '00-001', 'Polska'),
('Słoneczna', '5', 'Krakow', '30-002', 'Polska'),
('Główna', '1', 'Poznan', '60-003', 'Polska'),
('Leśna', '3', 'Wroclaw', '50-004', 'Polska'),
('Polna', '7', 'Gdansk', '80-005', 'Polska'),
('Zielona', '12', 'Lodz', '90-006', 'Polska'),
('Brzozowa', '8', 'Szczecin', '70-007', 'Polska'),
('Jesionowa', '15', 'Lublin', '20-008', 'Polska'),
('Dębowa', '20', 'Bialystok', '15-009', 'Polska'),
('Krótka', '1', 'Warszawa', '00-010', 'Polska');


-- Dodanie użytkowników z adresami
INSERT INTO users (permission_id, address_id, email, password_hash, name, surname, phone) VALUES
(1, 1, 'piotr@example.com', '$2y$10$BtygFQ1jPkZTl3gjHfM5v.bk1MIgPm5ZMpS0lYikgLyuP8Ve2Txxy', 'Piotr', 'Nowak', '123456789'),
(1, 2, 'anna@example.com', '$2y$10$BtygFQ1jPkZTl3gjHfM5v.bk1MIgPm5ZMpS0lYikgLyuP8Ve2Txxy', 'Anna', 'Kowalska', '987654321'),
(2, 3, 'admin@example.com', '$2y$10$BtygFQ1jPkZTl3gjHfM5v.bk1MIgPm5ZMpS0lYikgLyuP8Ve2Txxy', 'Adam', 'Admin', '555555555'),
(1, 4, 'magda@example.com', '$2y$10$BtygFQ1jPkZTl3gjHfM5v.bk1MIgPm5ZMpS0lYikgLyuP8Ve2Txxy', 'Magda', 'Wiśniewska', '111222333'),
(1, 5, 'marek@example.com', '$2y$10$BtygFQ1jPkZTl3gjHfM5v.bk1MIgPm5ZMpS0lYikgLyuP8Ve2Txxy', 'Marek', 'Zieliński', '444555666'),
(1, 6, 'katarzyna@example.com', '$2y$10$BtygFQ1jPkZTl3gjHfM5v.bk1MIgPm5ZMpS0lYikgLyuP8Ve2Txxy', 'Katarzyna', 'Mazur', '222333444'),
(1, 7, 'tomasz@example.com', '$2y$10$BtygFQ1jPkZTl3gjHfM5v.bk1MIgPm5ZMpS0lYikgLyuP8Ve2Txxy', 'Tomasz', 'Lewandowski', '333444555'),
(1, 8, 'ewa@example.com', '$2y$10$BtygFQ1jPkZTl3gjHfM5v.bk1MIgPm5ZMpS0lYikgLyuP8Ve2Txxy', 'Ewa', 'Kaczmarek', '444555666'),
(1, 9, 'jakub@example.com', '$2y$10$BtygFQ1jPkZTl3gjHfM5v.bk1MIgPm5ZMpS0lYikgLyuP8Ve2Txxy', 'Jakub', 'Kowalczyk', '555666777'),
(2, 10, 'superadmin@example.com', '$2y$10$BtygFQ1jPkZTl3gjHfM5v.bk1MIgPm5ZMpS0lYikgLyuP8Ve2Txxy', 'Super', 'Admin', '666777888');

-- Dodanie recenzji sklepu
INSERT INTO store_reviews (user_id, rating, comment) VALUES
(1, 5, 'Zamówienie przyszło bardzo szybko! Laptop świetnej jakości, polecam każdemu!'),
(2, 4, 'Dobry sklep, ale mogliby poprawić czas dostawy.'),
(4, 3, 'Obsługa klienta nie była zbyt pomocna, ale produkt ok.'),
(5, 5, 'Super doświadczenie, wszystko zgodne z opisem!'),
(1, 4, 'Produkt dobry, ale przesyłka trochę się opóźniła.');


-- Dodanie kategorii
INSERT INTO categories (name) VALUES ('Laptopy');
INSERT INTO categories (name) VALUES ('Smartfony');
INSERT INTO categories (name) VALUES ('Komputery');
INSERT INTO categories (name) VALUES ('Monitory');

-- Dodanie zniżek
INSERT INTO discounts (discount_percent, name) VALUES
(15.00, 'Promocja na laptopy'),
(10.00, 'Zniżka na smartfony'),
(20.00, 'Obniżka na komputery'),
(12.50, 'Promocja na monitory');


-- Wygaśnięta zniżka do testów dodana na Gram 17
INSERT INTO discounts (discount_percent, name, start_date, end_date, active) VALUES
(25.00, 'Wygasła zniżka', CURRENT_DATE - INTERVAL '10 days', CURRENT_DATE - INTERVAL '5 days', TRUE);



-- Atrybuty dla Laptopy 
INSERT INTO attributes (name, unit) VALUES 
('Procesor', NULL),
('Rozmiar ekranu', '″'),
('Pojemność baterii', 'Wh'),
('Pamięć RAM', 'GB'),
('Dysk SSD', 'GB');

-- Atrybuty dla Smartfony 
INSERT INTO attributes (name, unit) VALUES 
('Pojemność baterii', 'mAh'),
('Aparat', 'Mpx'),
('Pamięć wbudowana', 'GB');

-- Atrybuty dla Komputery 
INSERT INTO attributes (name, unit) VALUES 
('Procesor', NULL),
('Dysk twardy', 'GB'),
('Karta graficzna', NULL),
('Zasilacz', 'W');

-- Atrybuty dla Monitory 
INSERT INTO attributes (name, unit) VALUES 
('Rozdzielczość', NULL),
('Częstotliwość odświeżania', 'Hz'),
('Typ matrycy', NULL),
('Czas reakcji', 'ms');

-- Dodanie 10 laptopów
INSERT INTO products (name, brand, description, price, stock, image_url, category_id, discount_id) VALUES
('Laptop X1 Carbon Gen 9', 'Lenovo', 'Nowoczesny ultrabook z procesorem Intel Core i7 11. generacji i ekranem 14" Full HD. Idealny do pracy biurowej i podróży dzięki lekkiej konstrukcji i wydajnej baterii. Posiada podświetlaną klawiaturę i solidną, aluminiową obudowę. Zapewnia wysoki komfort użytkowania i szybki dostęp do danych.', 5700.00, 10, '../assets/images/laptopPage/placeholder1.jpg', 1,1),
('MacBook Air M2', 'Apple', 'Wyposażony w cichy i energooszczędny układ Apple M2, MacBook Air to znakomite narzędzie dla kreatywnych użytkowników. Smukła konstrukcja i lekka waga czynią go idealnym do pracy mobilnej. Ekran Retina zapewnia niesamowitą jakość obrazu. Bateria wystarcza nawet na 18 godzin pracy.', 6800.00, 7, '../assets/images/laptopPage/placeholder2.jpg', 1, NULL),
('Swift X 14', 'Acer', 'Laptop stworzony z myślą o twórcach i programistach. Procesor AMD Ryzen 7 i dedykowana grafika NVIDIA RTX 3050 gwarantują płynną pracę nawet przy wymagających zadaniach. Aluminiowa obudowa i szybki dysk SSD NVMe dopełniają całości. Laptop idealny dla osób ceniących wydajność i mobilność.', 4900.00, 12, '../assets/images/laptopPage/placeholder3.jpg', 1, NULL),
('XPS 13 Plus', 'Dell', 'Elegancki ultrabook z ekranem OLED 13.4" i nowoczesnym designem. Wewnątrz kryje się Intel Core i7 13. generacji i 16 GB pamięci RAM. Touchpad i klawiatura zapewniają doskonałe wrażenia podczas pisania. Idealny wybór dla profesjonalistów ceniących jakość i styl.', 7200.00, 5, '../assets/images/laptopPage/placeholder1.jpg', 1, NULL),
('ZenBook 14 OLED', 'ASUS', 'Smukły i wydajny laptop z ekranem OLED o doskonałej jakości obrazu. Zasilany procesorem Intel Core i5 oraz dyskiem SSD o pojemności 512 GB. Bateria pozwala na cały dzień pracy. To świetne połączenie elegancji i funkcjonalności.', 4600.00, 9, '../assets/images/laptopPage/placeholder2.jpg', 1, 1),
('Pavilion 15', 'HP', 'Uniwersalny laptop do codziennego użytku. Wyposażony w ekran 15.6", procesor AMD Ryzen 5 i grafikę Radeon Vega. Oferuje dobre osiągi przy korzystnej cenie. Sprawdzi się zarówno w pracy, jak i rozrywce.', 3400.00, 14, '../assets/images/laptopPage/placeholder3.jpg', 1, NULL),
('IdeaPad Gaming 3', 'Lenovo', 'Laptop stworzony z myślą o graczach szukających wydajności w przystępnej cenie. Posiada kartę graficzną NVIDIA GTX 1650 oraz procesor AMD Ryzen 5. System chłodzenia dba o optymalną temperaturę. Ekran 120 Hz zapewnia płynność w grach.', 4100.00, 8, '../assets/images/laptopPage/placeholder1.jpg', 1,NULL),
('MateBook D15', 'Huawei', 'Stylowy laptop z ekranem FullView 15.6". Lekki i cienki, a jednocześnie solidny. Wyposażony w procesor Intel Core i5 i szybki dysk SSD. Dobrze sprawdza się w pracy zdalnej i nauce.', 3200.00, 16, '../assets/images/laptopPage/placeholder2.jpg', 1,1),
('TUF F15', 'ASUS', 'Wydajny laptop gamingowy z grafiką RTX 4060 i procesorem Intel Core i7. Wyposażony w ekran 144 Hz oraz podświetlaną klawiaturę. Solidna konstrukcja spełnia wojskowe normy wytrzymałości. Idealny dla graczy i twórców.', 5900.00, 6, '../assets/images/laptopPage/placeholder3.jpg', 1,NULL),
('Gram 17', 'LG', 'Ultralekki laptop z dużym ekranem 17". Waży zaledwie 1,35 kg, co czyni go wyjątkowym w swojej klasie. Posiada pojemną baterię i procesor Intel Core i7. Świetny wybór do pracy w podróży.', 6700.00, 4, '../assets/images/laptopPage/placeholder1.jpg', 1,5);


--Dodanie 10 smartphonów
INSERT INTO products (name, brand, description, price, stock, image_url, category_id, discount_id) VALUES
('iPhone 14', 'Apple', 'Smartfon premium z systemem iOS. Wyposażony w procesor A15 Bionic i zaawansowany aparat. Posiada elegancki design i bardzo dobrą optymalizację systemu. Idealny dla fanów ekosystemu Apple.', 5200.00, 12, '../assets/images/smartphonePage/placeholder3.jpg', 2, NULL),
('Galaxy S22', 'Samsung', 'Flagowy smartfon z ekranem AMOLED i wsparciem dla 5G. Oferuje świetne zdjęcia i płynne działanie. Dzięki OneUI działa intuicyjnie. Posiada wodoodporną obudowę.', 4900.00, 10, '../assets/images/smartphonePage/placeholder1.jpg', 2, 2),
('Pixel 7', 'Google', 'Czysty Android prosto od Google z szybkim dostępem do aktualizacji. Wyposażony w inteligentny aparat i unikalny design. Doskonały dla osób szukających prostoty i płynności. Działa bardzo stabilnie.', 4300.00, 8, '../assets/images/smartphonePage/placeholder2.jpg', 2, 2),
('Redmi Note 11', 'Xiaomi', 'Budżetowy telefon o dużych możliwościach. Wyposażony w szybkie ładowanie i wydajną baterię. Ma wyraźny ekran AMOLED i solidną obudowę. Idealny do codziennego użytku.', 1200.00, 20, '../assets/images/smartphonePage/placeholder1.jpg', 2, NULL),
('P40 Pro', 'Huawei', 'Smartfon z potrójnym aparatem Leica do profesjonalnych zdjęć. Brak usług Google kompensuje świetna optymalizacja i sprzęt. Wyświetlacz OLED oferuje wysoką jakość obrazu. Świetny wybór dla entuzjastów fotografii.', 3100.00, 9, '../assets/images/smartphonePage/placeholder1.jpg', 2, NULL),
('Moto G100', 'Motorola', 'Wydajny telefon do pracy i rozrywki. Posiada mocny procesor Snapdragon i czystego Androida. Bateria starcza na dwa dni normalnego użytkowania. Dodatkowo oferuje Ready For – tryb desktopowy.', 1500.00, 14, '../assets/images/smartphonePage/placeholder2.jpg', 2, NULL),
('OnePlus 10T', 'OnePlus', 'Flagowy model z błyskawicznym ładowaniem 150W. Świetna wydajność i płynność dzięki OxygenOS. Telefon zapewnia doskonały stosunek jakości do ceny. Ekran AMOLED jest idealny do multimediów.', 3800.00, 7, '../assets/images/smartphonePage/placeholder3.jpg', 2, NULL),
('Xperia 5 IV', 'Sony', 'Kompaktowy smartfon dla miłośników multimediów. Świetna jakość dźwięku i zaawansowane funkcje aparatu. Ma unikalne proporcje ekranu 21:9. Sony oferuje również dedykowane tryby dla twórców wideo.', 4700.00, 6, '../assets/images/smartphonePage/placeholder1.jpg', 2, NULL),
('Galaxy A53', 'Samsung', 'Średniopółkowy smartfon z dobrym ekranem i pojemną baterią. Oferuje wiele funkcji znanych z flagowców. Dzięki OneUI działa płynnie i intuicyjnie. Doskonały wybór w rozsądnej cenie.', 1900.00, 18, '../assets/images/smartphonePage/placeholder1.jpg', 2, 2),
('Realme GT Neo 3', 'Realme', 'Telefon dla graczy i entuzjastów wydajności. Posiada ekran 120Hz i bardzo szybkie ładowanie. Świetnie sprawdzi się również do fotografii. Realme UI oferuje dużo personalizacji.', 2400.00, 11, '../assets/images/smartphonePage/placeholder2.jpg', 2, NULL);

-- Dodanie 10 komputerów stacjonarnych
INSERT INTO products (name, brand, description, price, stock, image_url, category_id, discount_id) VALUES
('ThinkCentre M90t', 'Lenovo', 'Biznesowy komputer stacjonarny z procesorem Intel Core i7 12. generacji i 16 GB RAM. Wyposażony w szybki dysk SSD oraz liczne porty do podłączania urządzeń peryferyjnych. Niezawodny sprzęt do biura i pracy zdalnej. Charakteryzuje się wysoką kulturą pracy i kompaktową obudową.', 4200.00, 10, '../assets/images/computerPage/placeholder1.jpg', 3, NULL),
('Mac Mini M2', 'Apple', 'Kompaktowy i cichy komputer z procesorem Apple M2. Świetnie sprawdza się w edycji wideo, grafice i pracy z wieloma aplikacjami jednocześnie. Mimo niewielkich rozmiarów oferuje dużą wydajność. Działa niezwykle płynnie i energooszczędnie.', 4800.00, 8, '../assets/images/computerPage/placeholder2.jpg', 3, NULL),
('Vostro 3681', 'Dell', 'Klasyczny komputer stacjonarny dla małych firm i użytkowników domowych. Procesor Intel Core i5 10. generacji zapewnia dobrą wydajność przy niskim zużyciu energii. Obudowa typu mini tower oszczędza miejsce na biurku. Komputer gotowy do pracy od razu po podłączeniu.', 2600.00, 15, '../assets/images/computerPage/placeholder3.jpg', 3, NULL),
('iMac 24"', 'Apple', 'Elegancki komputer typu all-in-one z ekranem Retina 4.5K. Wyposażony w układ Apple M1, zapewnia wysoką wydajność i doskonałą jakość obrazu. Idealny dla grafików, projektantów i osób pracujących kreatywnie. Łączy nowoczesny wygląd z funkcjonalnością.', 6700.00, 6, '../assets/images/computerPage/placeholder1.jpg', 3, NULL),
('IdeaCentre 5', 'Lenovo', 'Domowy komputer o szerokim zastosowaniu. Wyposażony w procesor AMD Ryzen 7 oraz 512 GB SSD. Oferuje płynną pracę w aplikacjach biurowych, edukacyjnych i multimedialnych. Nowoczesny design i kompaktowa obudowa idealnie wpasują się w każde wnętrze.', 3100.00, 11, '../assets/images/computerPage/placeholder2.jpg', 3, 3),
('Omen 25L', 'HP', 'Komputer stworzony dla graczy i entuzjastów. Zawiera kartę graficzną RTX 3060 oraz procesor Intel Core i7. Zapewnia wysoką wydajność w grach i programach graficznych. Stylowa obudowa z podświetleniem RGB wyróżnia go na tle konkurencji.', 5900.00, 5, '../assets/images/computerPage/placeholder3.jpg', 3, NULL),
('Aspire TC', 'Acer', 'Uniwersalny komputer do codziennych zadań. Intel Core i3 i 8 GB RAM wystarczają do przeglądania internetu, pracy biurowej i multimediów. Pojemny dysk HDD oraz złącze HDMI czynią go praktycznym wyborem. Działa cicho i sprawnie.', 2000.00, 20, '../assets/images/computerPage/placeholder1.jpg', 3, NULL),
('ROG Strix G10DK', 'ASUS', 'Gamingowy komputer z procesorem Ryzen 5 i grafiką GeForce GTX 1660. Zapewnia płynną rozgrywkę w popularnych tytułach. Stylowa obudowa z podświetleniem RGB i wydajne chłodzenie to jego atuty. Gotowy na intensywną rozrywkę.', 4400.00, 9, '../assets/images/computerPage/placeholder2.jpg', 3, 3),
('ZBOX Magnus EN', 'Zotac', 'Mini-PC o zaskakującej mocy. Zawiera grafikę NVIDIA RTX 3070 i procesor Intel Core i7. Kompaktowy rozmiar idealny do pracy w ograniczonej przestrzeni. Świetnie sprawdza się w zadaniach graficznych i montażu wideo.', 7200.00, 3, '../assets/images/computerPage/placeholder3.jpg', 3, NULL),
('EliteDesk 800 G6', 'HP', 'Profesjonalny komputer biurowy z procesorem Intel Core i5 i szybkim SSD. Zoptymalizowany pod kątem bezpieczeństwa i wydajności. Obsługuje wiele monitorów i urządzeń zewnętrznych. Polecany do środowisk korporacyjnych i instytucjonalnych.', 3600.00, 13, '../assets/images/computerPage/placeholder1.jpg', 3, NULL);

-- Dodanie 10 monitorów
INSERT INTO products (name, brand, description, price, stock, image_url, category_id, discount_id) VALUES
('Monitor UltraSharp U2723QE', 'Dell', '27-calowy monitor 4K z matrycą IPS o świetnym odwzorowaniu kolorów. Idealny do pracy biurowej, graficznej oraz zdalnego nauczania. Posiada złącze USB-C i ergonomiczny stojak. Certyfikat ComfortView Plus redukuje zmęczenie oczu.', 3200.00, 12, '../assets/images/monitorPage/placeholder1.jpg', 4, 4),
('Monitor Pro Display XDR', 'Apple', 'Zaawansowany monitor 6K przeznaczony dla profesjonalistów. Oferuje niezwykle wysoką jasność i kontrast, idealny do postprodukcji wideo i edycji zdjęć. Obudowa z aluminium i siateczkowym wzorem zapewnia optymalne chłodzenie. Najwyższa jakość dla wymagających.', 25000.00, 2, '../assets/images/monitorPage/placeholder2.jpg', 4, NULL),
('Monitor Odyssey G7', 'Samsung', 'Zakrzewiony monitor gamingowy QHD z odświeżaniem 240 Hz. Doskonały wybór dla graczy szukających szybkości i immersji. Technologia HDR600 i matryca VA zapewniają żywe kolory i głęboki kontrast. Z tyłu efektowne podświetlenie RGB.', 3300.00, 7, '../assets/images/monitorPage/placeholder3.jpg', 4, NULL),
('Monitor ThinkVision P27h-30', 'Lenovo', 'Profesjonalny monitor WQHD z portem USB-C i bardzo cienkimi ramkami. Przeznaczony do pracy z wieloma oknami jednocześnie. Oferuje szerokie kąty widzenia i doskonałą ostrość obrazu. Kompatybilny z VESA i z ergonomiczną podstawą.', 2200.00, 10, '../assets/images/monitorPage/placeholder1.jpg', 4, NULL),
('Monitor Predator XB3', 'Acer', 'Gamingowy monitor 144 Hz z matrycą IPS i rozdzielczością Full HD. Wspiera technologię NVIDIA G-SYNC dla płynnej rozgrywki. Charakteryzuje się dynamicznym designem i regulowaną podstawą. Świetny wybór dla e-sportowców.', 1900.00, 14, '../assets/images/monitorPage/placeholder2.jpg', 4, NULL),
('Monitor Eizo EV2485', 'Eizo', '24-calowy monitor biurowy z panelem IPS i złączem USB-C. Zapewnia doskonałą jakość obrazu i energooszczędną pracę. Ma elegancki, minimalistyczny wygląd. Idealny do długotrwałego użytkowania.', 2100.00, 6, '../assets/images/monitorPage/placeholder3.jpg', 4, NULL),
('Monitor TUF Gaming VG289Q', 'ASUS', 'Monitor 4K UHD z obsługą HDR i technologią FreeSync. Matryca IPS zapewnia świetne kąty widzenia i wyraziste kolory. Stworzony z myślą o graczach i twórcach treści. Solidna konstrukcja i wszechstronna regulacja.', 1650.00, 8, '../assets/images/monitorPage/placeholder1.jpg', 4, 4),
('Monitor LG UltraFine 5K', 'LG', 'Profesjonalny monitor 5K z obsługą Thunderbolt 3. Zapewnia niezwykle szczegółowy obraz i wierne odwzorowanie barw. Doskonały do pracy z grafiką, zdjęciami i wideo. Kompatybilny z macOS.', 5500.00, 4, '../assets/images/monitorPage/placeholder2.jpg', 4, NULL),
('Monitor Philips 243V7QDSB', 'Philips', 'Niedrogi monitor Full HD z matrycą IPS i technologią Flicker-Free. Świetny wybór do użytku domowego i biura. Ma nowoczesny wygląd i cienkie ramki. Działa energooszczędnie i bez migotania.', 580.00, 20, '../assets/images/monitorPage/placeholder3.jpg', 4, NULL),
('Monitor MSI Optix MAG274QRF', 'MSI', 'Szybki monitor QHD z czasem reakcji 1 ms i częstotliwością 165 Hz. Wyposażony w technologię Adaptive Sync i panel IPS. Oferuje wysoką płynność i doskonałe kolory. Doskonały dla graczy wymagających szybkości i precyzji.', 1700.00, 9, '../assets/images/monitorPage/placeholder1.jpg', 4, NULL);



-- Atrybuty dla laptopów
INSERT INTO product_attributes (product_id, attribute_id, value) VALUES
(1, 1, 'Intel Core i7-1165G7'),
(1, 2, '14'),
(1, 3, '52'),
(1, 4, '16'),
(1, 5, '512'),

(2, 1, 'AMD Ryzen 5 4500U'),
(2, 2, '14'),
(2, 3, '48'),
(2, 4, '8'),
(2, 5, '256'),

(3, 1, 'Apple M1'),
(3, 2, '13.3'),
(3, 3, '49.9'),
(3, 4, '8'),
(3, 5, '512'),

(4, 1, 'Intel Core i5-1035G1'),
(4, 2, '15.6'),
(4, 3, '41'),
(4, 4, '8'),
(4, 5, '256'),

(5, 1, 'AMD Ryzen 7 4700U'),
(5, 2, '15.6'),
(5, 3, '42'),
(5, 4, '16'),
(5, 5, '512'),

(6, 1, 'Intel Core i3-10110U'),
(6, 2, '15.6'),
(6, 3, '36'),
(6, 4, '4'),
(6, 5, '256'),

(7, 1, 'Intel Core i5-1135G7'),
(7, 2, '15.6'),
(7, 3, '45'),
(7, 4, '8'),
(7, 5, '512'),

(8, 1, 'Intel Core i7-1165G7'),
(8, 2, '14'),
(8, 3, '60'),
(8, 4, '16'),
(8, 5, '1024'),

(9, 1, 'AMD Ryzen 9 5900HX'),
(9, 2, '15.6'),
(9, 3, '48'),
(9, 4, '32'),
(9, 5, '1024'),

(10, 1, 'Intel Core i7-1185G7'),
(10, 2, '13.4'),
(10, 3, '56'),
(10, 4, '16'),
(10, 5, '512');

-- Atrybuty dla smartfonów
INSERT INTO product_attributes (product_id, attribute_id, value) VALUES
(11, 2, '6.1'),
(11, 6, '3100'),
(11, 7, '12'),
(11, 8, '64'),
(11, 4, '4'),

(12, 2, '6.5'),
(12, 6, '4000'),
(12, 7, '48'),
(12, 8, '128'),
(12, 4, '6'),

(13, 2, '6.7'),
(13, 6, '4500'),
(13, 7, '108'),
(13, 8, '256'),
(13, 4, '8'),

(14, 2, '5.8'),
(14, 6, '2800'),
(14, 7, '12'),
(14, 8, '64'),
(14, 4, '3'),

(15, 2, '6.3'),
(15, 6, '3500'),
(15, 7, '64'),
(15, 8, '128'),
(15, 4, '6'),

(16, 2, '6.4'),
(16, 6, '5000'),
(16, 7, '64'),
(16, 8, '128'),
(16, 4, '6'),

(17, 2, '6.2'),
(17, 6, '3700'),
(17, 7, '48'),
(17, 8, '128'),
(17, 4, '6'),

(18, 2, '6.9'),
(18, 6, '5000'),
(18, 7, '108'),
(18, 8, '512'),
(18, 4, '12'),

(19, 2, '6.5'),
(19, 6, '4000'),
(19, 7, '64'),
(19, 8, '256'),
(19, 4, '8'),

(20, 2, '6.3'),
(20, 6, '4200'),
(20, 7, '64'),
(20, 8, '128'),
(20, 4, '6');

-- Atrybuty dla komputrów
INSERT INTO product_attributes (product_id, attribute_id, value) VALUES
(21, 9, 'Intel Core i7-12700'),
(21, 4, '16'),
(21, 10, '1000'),
(21, 11, 'NVIDIA RTX 3060'),
(21, 12, '650'),

(22, 9, 'AMD Ryzen 5 5600X'),
(22, 4, '32'),
(22, 10, '2000'),
(22, 11, 'AMD Radeon RX 6700 XT'),
(22, 12, '750'),

(23, 9, 'Intel Core i5-12400'),
(23, 4, '16'),
(23, 10, '512'),
(23, 11, 'NVIDIA GTX 1660 Super'),
(23, 12, '600'),

(24, 9, 'AMD Ryzen 7 5800X'),
(24, 4, '32'),
(24, 10, '1000'),
(24, 11, 'NVIDIA RTX 3070'),
(24, 12, '750'),

(25, 9, 'Intel Core i9-12900K'),
(25, 4, '64'),
(25, 10, '2000'),
(25, 11, 'NVIDIA RTX 3080 Ti'),
(25, 12, '850'),

(26, 9, 'AMD Ryzen 9 5900X'),
(26, 4, '32'),
(26, 10, '1500'),
(26, 11, 'NVIDIA RTX 3080'),
(26, 12, '750'),

(27, 9, 'Intel Core i3-12100'),
(27, 4, '8'),
(27, 10, '256'),
(27, 11, 'Intel UHD Graphics 730'),
(27, 12, '500'),

(28, 9, 'AMD Ryzen 5 3600'),
(28, 4, '16'),
(28, 10, '1000'),
(28, 11, 'NVIDIA GTX 1650'),
(28, 12, '600'),

(29, 9, 'Intel Core i7-11700K'),
(29, 4, '32'),
(29, 10, '2000'),
(29, 11, 'NVIDIA RTX 3060 Ti'),
(29, 12, '700'),

(30, 9, 'AMD Ryzen 7 3700X'),
(30, 4, '16'),
(30, 10, '1000'),
(30, 11, 'AMD Radeon RX 5700 XT'),
(30, 12, '650');

-- Atrybuty dla monitorów
INSERT INTO product_attributes (product_id, attribute_id, value) VALUES
(31, 2, '24'),
(31, 13, '1920x1080'),
(31, 14, '75'),
(31, 15, 'IPS'),
(31, 16, '5'),

(32, 2, '27'),
(32, 13, '2560x1440'),
(32, 14, '144'),
(32, 15, 'VA'),
(32, 16, '4'),

(33, 2, '32'),
(33, 13, '3840x2160'),
(33, 14, '60'),
(33, 15, 'IPS'),
(33, 16, '8'),

(34, 2, '24'),
(34, 13, '1920x1080'),
(34, 14, '60'),
(34, 15, 'TN'),
(34, 16, '1'),

(35, 2, '27'),
(35, 13, '2560x1440'),
(35, 14, '165'),
(35, 15, 'IPS'),
(35, 16, '4'),

(36, 2, '34'),
(36, 13, '3440x1440'),
(36, 14, '100'),
(36, 15, 'VA'),
(36, 16, '5'),

(37, 2, '24'),
(37, 13, '1920x1080'),
(37, 14, '144'),
(37, 15, 'TN'),
(37, 16, '1'),

(38, 2, '27'),
(38, 13, '2560x1080'),
(38, 14, '75'),
(38, 15, 'IPS'),
(38, 16, '5'),

(39, 2, '32'),
(39, 13, '2560x1440'),
(39, 14, '144'),
(39, 15, 'VA'),
(39, 16, '4'),

(40, 2, '24'),
(40, 13, '1920x1080'),
(40, 14, '60'),
(40, 15, 'IPS'),
(40, 16, '5');


-- Recenzje
INSERT INTO product_reviews (user_id, product_id, rating, comment) VALUES
(1, 1, 5, 'Świetny laptop, działa bardzo szybko.'),
(2, 1, 4, 'Dobra jakość, bateria mogłaby być lepsza.'),
(3, 2, 3, 'Przeciętny, ale za tę cenę w porządku.'),
(4, 3, 4, 'Lekki i wygodny do pracy mobilnej.'),
(5, 3, 3, 'Ekran mógłby być jaśniejszy.'),
(6, 4, 2, 'Problemy z chłodzeniem, głośny wentylator.'),
(7, 5, 5, 'Idealny do gier, polecam!'),
(8, 6, 4, 'Bardzo wydajny, choć trochę ciężki.'),
(9, 7, 5, 'Świetna bateria i ekran.'),
(10, 8, 3, 'Dobra jakość wykonania.'),

(1, 9, 4, 'Bardzo szybki i lekki.'),
(2, 10, 5, 'Wysokiej klasy ultrabook, polecam.'),
(3, 11, 4, 'Bardzo dobry aparat, ekran super.'),
(4, 12, 5, 'Bateria trzyma cały dzień bez problemu.'),
(5, 12, 4, 'Solidny i szybki telefon.'),
(6, 13, 3, 'Standardowy smartfon, działa poprawnie.'),
(7, 14, 4, 'Szybki i responsywny.'),
(8, 15, 2, 'Problemy z aktualizacjami systemu.'),
(9, 16, 5, 'Bardzo ładny design i wyświetlacz.'),
(10, 17, 4, 'Telefon wart swojej ceny.'),

(1, 18, 3, 'Działa bez zarzutów, ale bez szału.'),
(2, 19, 5, 'Rewelacyjna jakość dźwięku.'),
(3, 20, 4, 'Bateria mogłaby być mocniejsza.'),
(4, 21, 5, 'Mocny procesor, idealny do pracy.'),
(5, 21, 4, 'Dobra karta graficzna, szybki dysk SSD.'),
(6, 22, 3, 'Nieco głośny wentylator.'),
(7, 23, 4, 'Stabilny system i szybki.'),
(8, 23, 3, 'Przeciętny, ale spełnia oczekiwania.'),
(9, 24, 2, 'Problemy z kompatybilnością oprogramowania.'),
(10, 25, 5, 'Rewelacyjny komputer do gier.'),

(1, 26, 4, 'Dobry stosunek jakości do ceny.'),
(2, 27, 3, 'Standardowy sprzęt, nic specjalnego.'),
(3, 28, 4, 'Szybki i niezawodny.'),
(4, 29, 5, 'Świetny do pracy biurowej.'),
(5, 30, 4, 'Działa płynnie, polecam.'),
(6, 31, 4, 'Świetna rozdzielczość, dobry kontrast.'),
(7, 32, 5, 'Bardzo szybki czas reakcji, idealny do gier.'),
(8, 33, 3, 'Standardowy monitor, działa poprawnie.'),
(9, 34, 4, 'Dobre odwzorowanie kolorów.'),
(10, 35, 2, 'Odświeżanie mogłoby być wyższe.'),

(1, 36, 5, 'Cichy i energooszczędny.'),
(2, 36, 4, 'Lekki i łatwy do ustawienia.'),
(3, 37, 3, 'Działa bez zarzutów.'),
(4, 38, 4, 'Bardzo dobry ekran, polecam.'),
(5, 39, 5, 'Idealny do pracy graficznej.'),
(6, 40, 4, 'Dobra jakość i niska cena.'),
(7, 1, 3, 'Laptop działa dobrze, ale bateria słaba.'),
(8, 5, 4, 'Dobrze wykonany sprzęt.'),
(9, 10, 5, 'Ultrabook z najwyższej półki.'),
(10, 15, 3, 'Telefon warty swojej ceny.');
