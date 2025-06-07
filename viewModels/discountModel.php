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
        $this->active = filter_var($data['active'], FILTER_VALIDATE_BOOLEAN);
    }
}
