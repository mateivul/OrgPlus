<?php

class User
{
    public ?int $id;
    public string $firstName;
    public string $lastName;
    public string $email;
    public string $passwordHash;
    public ?string $hashSalt = null;
    public ?string $registrationDate;
    public ?string $lastLoginDate;

    public function __construct(
        ?int $id,
        string $firstName,
        string $lastName,
        string $email,
        string $passwordHash,
        ?string $hashSalt = null,
        ?string $registrationDate = null,
        ?string $lastLoginDate = null
    ) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->hashSalt = $hashSalt;
        $this->registrationDate = $registrationDate;
        $this->lastLoginDate = $lastLoginDate;
    }

    public function verifyPassword(string $password): bool
    {
        $input_hashed_password = hash('sha256', $this->hashSalt . $password);
        return hash_equals($input_hashed_password, $this->passwordHash);
    }
}
