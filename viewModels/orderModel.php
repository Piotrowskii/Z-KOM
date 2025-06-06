<?php

class Order {
    public int $id;
    public int $userId;
    public float $total;
    public string $trackingId;
    public string $status;
    public string $createdAt;

    public function __construct(array $data) {
        $this->id = (int) $data['id'];
        $this->userId = (int) $data['user_id'];
        $this->total = (float) $data['total'];
        $this->trackingId = $data['tracking_id'];
        $this->status = $data['status'] ?? 'nowe';
        $this->createdAt = $data['created_at'];
    }

    public function isNew(): bool {
        return $this->status === 'nowe';
    }

    public function isCompleted(): bool {
        return strtolower($this->status) === 'zrealizowane';
    }

    public function isCancelled(): bool {
        return strtolower($this->status) === 'anulowane';
    }
}