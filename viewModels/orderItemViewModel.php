<?php

class OrderItemViewModel {
    public int $productId;     
    public ?int $orderId;
    public ?string $name;
    public int $quantity;
    public float $price;       
    public ?string $imageUrl;

    public function __construct(array $data) {
        $this->productId = (int)$data['id'];
        $this->orderId = (int)$data['order_id'];
        $this->name = $data['name'];
        $this->quantity = (int)$data['quantity'];
        $this->price = (float)$data['price'];
        $this->imageUrl = $data['image_url'] ?? null;
    }

    public function getFormattedPrice(): string {
        return number_format($this->price, 2) . " zł";
    }

    public function getTotalPrice(): float {
        return $this->price * $this->quantity;
    }

    public function getFormattedTotalPrice(): string {
        return number_format($this->getTotalPrice(), 2) . " zł";
    }

    public function isImageLocal(): bool {
        return is_string($this->imageUrl) && str_starts_with($this->imageUrl, '..');
    }

    public function doesProductExists(): bool {
        return $this->name !== null;
    }

}
