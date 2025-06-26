<?php

// namespace App\Model;

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

    // --- Comportament specific User-ului (logica de verificare adaptată) ---
    public function verifyPassword(string $password): bool
    {
        echo 'DEBUG User Model: Verificare parolă pentru email: ' . $this->email . '<br>';
        echo 'DEBUG User Model: Hashed password in DB: ' . $this->passwordHash . '<br>';

        $info = password_get_info($this->passwordHash);
        echo 'DEBUG User Model: password_get_info array: ' . json_encode($info) . '<br>';
        echo 'DEBUG User Model: password_get_info algo: ' . ($info['algo'] ?? 'N/A') . '<br>'; // Am adaugat ?? 'N/A' pentru a afisa si 'null' explicit

        // Condiția corectată AICI
        if ($info['algo']) {
            // Aceasta este varianta cea mai simplă și eficientă
            // Este un hash modern (bcrypt, argon2, etc.)
            echo 'DEBUG User Model: Se folosește password_verify (modern).<br>';
            return password_verify($password, $this->passwordHash);
        } else {
            // Este vechiul tău hash SHA256 + salt manual
            echo 'DEBUG User Model: Se folosește hash SHA256 + salt (vechi).<br>';
            echo 'DEBUG User Model: Old salt from DB: ' . ($this->oldSalt ?? 'NULL') . '<br>';

            if ($this->oldSalt !== null) {
                $input_hashed_password = hash('sha256', $this->oldSalt . $password);
                echo 'DEBUG User Model: Input parola hash-uită cu salt vechi: ' . $input_hashed_password . '<br>';
                echo 'DEBUG User Model: Comparare: Stored: ' .
                    $this->passwordHash .
                    ' vs Input: ' .
                    $input_hashed_password .
                    '<br>';
                return hash_equals($input_hashed_password, $this->passwordHash);
            }
            echo 'DEBUG User Model: oldSalt este NULL, nu se poate verifica vechiul hash. (Ar trebui să fie populat pentru userii vechi)<br>';
            return false;
        }
    }
}
