<?php

class StoreReviewViewModel {
    public int $id;
    public int $userId;
    public float $rating;
    public string $comment;
    public string $createdAt;
    public string $name;
    public string $surname;

    public function __construct(array $data) {
        $this->id = (int) $data['id'];
        $this->userId = (int)$data['user_id'] ?? null;
        $this->rating = $data['rating'] ?? null;
        $this->comment = $data['comment'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->surname = $data['surname'] ?? null;
    }
}
