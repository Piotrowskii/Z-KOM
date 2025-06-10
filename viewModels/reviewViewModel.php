<?php

class ReviewView {
    public int $id;
    public int $userId;
    public int $productId;
    public float $rating;
    public string $comment;
    public string $createdAt;
    public string $name;
    public string $surname;

    public function __construct(array $data) {
        $this->id = (int) $data['id'];
        $this->userId = (int) $data['user_id'];
        $this->productId = (int) $data['product_id'];
        $this->rating = (float) $data['rating'];
        $this->comment = $data['comment'];
        $this->createdAt = $data['created_at'];
        $this->name = $data['name'];
        $this->surname = $data['surname'];
    }
}
