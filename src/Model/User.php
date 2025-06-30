<?php

class User
{
    public ?int $id;
    public string $firstName;
    public string $lastName;
    public string $email;
    public string $passwordHash;
    public ?string $oldSalt = null;
    public ?string $registrationDate;
    public ?string $lastLoginDate;

    public function __construct(
        ?int $id,
        string $firstName,
        string $lastName,
        string $email,
        string $passwordHash,
        ?string $oldSalt = null,
        ?string $registrationDate = null,
        ?string $lastLoginDate = null
    ) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->oldSalt = $oldSalt;
        $this->registrationDate = $registrationDate;
        $this->lastLoginDate = $lastLoginDate;
    }

    public function verifyPassword(string $password): bool
    {
        $info = password_get_info($this->passwordHash);

        if ($info['algo']) {
            return password_verify($password, $this->passwordHash);
        } else {
            if ($this->oldSalt !== null) {
                $input_hashed_password = hash('sha256', $this->oldSalt . $password);
                return hash_equals($input_hashed_password, $this->passwordHash);
            }
            return false;
        }
    }
}
