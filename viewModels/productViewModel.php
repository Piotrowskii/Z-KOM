<?php

class ProductViewModel {
    public int $id;
    public string $name;
    public ?string $description;
    public string $brand;
    public float $price;
    public int $stock;
    public float $rating;
    public string $imageUrl;
    public ?int $categoryId;
    public ?int $discountId;
    public ?float $discountPercent;
    public float $finalPrice;

    public function __construct(array $data) {
        $this->id = (int)$data['id'];
        $this->name = $data['name'];
        $this->brand = $data['brand'];
        $this->rating = (float)$data['rating'];
        $this->description = $data['description'] ?? '';
        $this->price = (float)$data['price'];
        $this->stock = (int)$data['stock'];
        $this->imageUrl = $data['image_url'] ?? null;
        $this->categoryId = isset($data['category_id']) ? (int)$data['category_id'] : null;
        $this->discountId = isset($data['discount_id']) ? (int)$data['discount_id'] : null;
        $this->discountPercent = isset($data['discount_percent']) ? (float)$data['discount_percent'] : null;

        $this->finalPrice = isset($data['final_price']) 
            ? (float)$data['final_price'] 
            : round($this->price * (1 - ($this->discountPercent ?? 0) / 100), 2);
    }

    public function getFormattedPrice(): string {
        return number_format($this->price, 2) . " zł";
    }

    public function getFormattedFinalPrice(): string {
        return number_format($this->finalPrice, 2) . " zł";
    }

    public function hasDiscount(): bool {
        return $this->discountPercent !== null && $this->discountPercent > 0;
    }
}

