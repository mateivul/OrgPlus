
<?php // src/Repository/UserRepository.php
// src/Repository/UserRepository.php
// src/Repository/UserRepository.php
// src/Repository/UserRepository.php
// No namespace or use statements

class UserRepository
{
    private PDO $pdo; // O singură proprietate PDO

    public function __construct(PDO $pdo)
    {
        // Un singur constructor
        $this->pdo = $pdo;
    }

    /**
     * Găsește un utilizator după ID.
     *
     * @param int $id
     * @return User|null
     */
    public function find(int $id): ?User
    {
        // Păstrăm numele "find" și o facem completă
        // Asigură-te că numele coloanelor se potrivesc cu schema ta de bază de date
        // (Ex: first_name, last_name, password_hash, old_salt, registration_date, last_login_date)
        $sql = 'SELECT id, name, prenume, email, password, hash_salt, created_at, last_login FROM users WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            return null;
        }

        // Mapează corect la constructorul App\Entity\User
        return new User(
            $userData['id'],
            $userData['name'], // Folosim 'name' din DB
            $userData['prenume'], // Folosim 'prenume' din DB
            $userData['email'],
            $userData['password'],
            $userData['hash_salt'],
            $userData['created_at'], // Folosim 'created_at' din DB pentru registrationDate
            $userData['last_login']
        );
    }

    // Aici este modificarea cheie: $this->dbConnection înlocuit cu $this->pdo
    public function findById(int $id): ?User
    {
        // Folosește $this->pdo, nu $this->dbConnection
        $stmt = $this->pdo->prepare(
            // <-- Modificat AICI
            // Am corectat numele coloanelor la 'name', 'prenume', 'created_at' - ATENTIE, aici sunt folosite numele VECHI!
            // Te-aș sfătui să folosești numele standardizate (first_name, last_name etc.)
            'SELECT id, name, prenume, email, password, hash_salt, created_at, last_login FROM users WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            return new User(
                $userData['id'],
                $userData['name'], // Folosim 'name' din DB
                $userData['prenume'], // Folosim 'prenume' din DB
                $userData['email'],
                $userData['password'],
                $userData['hash_salt'],
                $userData['created_at'], // Folosim 'created_at' din DB pentru registrationDate
                $userData['last_login']
            );
        }
        return null;
    }

    /**
     * Găsește un utilizator după email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        $sql =
            'SELECT id, name, prenume, email, password, hash_salt, created_at, last_login FROM users WHERE email = :email';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            return null;
        }

        // Mapează corect la constructorul App\Entity\User
        return new User(
            $userData['id'],
            $userData['name'], // Folosim 'name' din DB
            $userData['prenume'], // Folosim 'prenume' din DB
            $userData['email'],
            $userData['password'],
            $userData['hash_salt'],
            $userData['created_at'], // Folosim 'created_at' din DB pentru registrationDate
            $userData['last_login']
        );
    }

    /**
     * Actualizează data ultimului login pentru un utilizator.
     *
     * @param User $user
     * @return bool
     */
    public function updateLastLogin(User $user): bool
    {
        $currentTime = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_date = :lastLogin WHERE id = :id'); // <-- Nume coloană corectat
        $stmt->bindValue(':lastLogin', $currentTime);
        $stmt->bindValue(':id', $user->id, PDO::PARAM_INT);

        $result = $stmt->execute();
        if ($result) {
            $user->lastLoginDate = $currentTime;
        }
        return $result;
    }

    /**
     * Actualizează hash-ul parolei unui utilizator.
     *
     * @param int $userId
     * @param string $newPasswordHash
     * @param string|null $oldSalt
     * @return bool
     */
    public function updatePasswordHash(int $userId, string $newPasswordHash, ?string $oldSalt = null): bool
    {
        // Modificat coloana 'password' la 'password_hash' și 'hash_salt' la 'old_salt'
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_hash = :newPasswordHash, old_salt = :oldSalt WHERE id = :id'
        );
        $stmt->bindValue(':newPasswordHash', $newPasswordHash);
        $stmt->bindValue(':oldSalt', $oldSalt); // Poate fi null
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Salvează un utilizator nou în baza de date.
     *
     * @param User $user
     * @return int|false ID-ul utilizatorului nou inserat sau false la eșec.
     */
    public function save(User $user): int|false
    {
        // Returnează int|false conform practicii PDO lastInsertId()
        try {
            // Nume coloane aliniate cu App\Entity\User și schema obișnuită
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (first_name, last_name, email, password_hash, old_salt, registration_date)
                 VALUES (:first_name, :last_name, :email, :password_hash, :old_salt, :registration_date)'
            );

            $stmt->bindValue(':first_name', $user->firstName);
            $stmt->bindValue(':last_name', $user->lastName);
            $stmt->bindValue(':email', $user->email);
            $stmt->bindValue(':password_hash', $user->passwordHash);
            $stmt->bindValue(':old_salt', $user->oldSalt); // Poate fi NULL
            $stmt->bindValue(':registration_date', $user->registrationDate ?? date('Y-m-d H:i:s')); // Dă o valoare default dacă e null

            $result = $stmt->execute();

            if ($result) {
                $user->id = (int) $this->pdo->lastInsertId();
                return $user->id; // Returnează ID-ul
            } else {
                // Afișează erorile SQL
                error_log('Eroare la execuția INSERT în UserRepository: ' . implode(' ', $stmt->errorInfo()));
                return false;
            }
        } catch (PDOException $e) {
            error_log('Eroare PDO la salvarea utilizatorului în UserRepository: ' . $e->getMessage());
            return false;
        }
    }
}
