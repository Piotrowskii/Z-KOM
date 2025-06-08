<?php

// W php istnieje już klasa Attribute
class DbAttribute {
    public int $id;
    public string $name;
    public ?string $unit;

    public function __construct(array $data) {
        $this->id = (int) ($data['id'] ?? 0);
        $this->name = $data['name'] ?? '';
        $this->unit = $data['unit'] ?? null;
    }
}
