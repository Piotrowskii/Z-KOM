<?php

class Discount {
    public int $id;
    public float $discountPercent;
    public string $name;
    public string $startDate;
    public string $endDate;
    public bool $active;

    public function __construct(array $data) {
        $this->id = (int) $data['id'];
        $this->discountPercent = (float) $data['discount_percent'];
        $this->name = $data['name'];
        $this->startDate = $data['start_date'];
        $this->endDate = $data['end_date'];
        $this->active = ($data['active'] === 't');
    }

    public function isActive(): bool
    {
        $now = new DateTime();
        $startDate = new DateTime($this->startDate);
        $endDate = new DateTime($this->endDate);

        return $this->active && $now >= $startDate && $now <= $endDate;
    }
}
