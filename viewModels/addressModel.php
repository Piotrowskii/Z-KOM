<?php

class Address {
    public int $id;
    public string $street;
    public string $city;
    public string $houseNumber;
    public string $postalCode;
    public string $country;

    public function __construct(array $data) {
        $this->id = (int) $data['id'];
        $this->street = $data['street_name'];
        $this->houseNumber = $data['house_number'];
        $this->city = $data['city'];
        $this->postalCode = $data['postal_code'];
        $this->country = $data['country'];
    }
}

