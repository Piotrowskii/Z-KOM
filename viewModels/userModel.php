<?php

class User {
    public int $id;
    public int $permissionId;
    public ?int $addressId;
    public string $email;
    public string $name;
    public string $surname;
    public ?string $phone;
    public string $createdAt;

    public function __construct(array $data) {
        $this->id = (int) $data['id'];
        $this->permissionId = (int) $data['permission_id'];
        $this->addressId = isset($data['address_id']) ? (int)$data['address_id'] : null;
        $this->email = $data['email'];
        $this->name = $data['name'] ?? '';
        $this->surname = $data['surname'] ?? '';
        $this->phone = $data['phone'] ?? null;
        $this->createdAt = $data['created_at'];
    }

    public function hasAddress(): bool {
        return $this->addressId !== null;
    }
}