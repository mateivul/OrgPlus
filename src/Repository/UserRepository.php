<?php

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function find(int $id): ?User
    {
        $sql = 'SELECT id, name, prenume, email, password, hash_salt, created_at, last_login FROM users WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            return null;
        }

        return new User(
            $userData['id'],
            $userData['name'],
            $userData['prenume'],
            $userData['email'],
            $userData['password'],
            $userData['hash_salt'],
            $userData['created_at'],
            $userData['last_login']
        );
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, prenume, email, password, hash_salt, created_at, last_login FROM users WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            return new User(
                $userData['id'],
                $userData['name'],
                $userData['prenume'],
                $userData['email'],
                $userData['password'],
                $userData['hash_salt'],
                $userData['created_at'],
                $userData['last_login']
            );
        }
        return null;
    }

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

        return new User(
            $userData['id'],
            $userData['name'],
            $userData['prenume'],
            $userData['email'],
            $userData['password'],
            $userData['hash_salt'],
            $userData['created_at'],
            $userData['last_login']
        );
    }

    public function updateLastLogin(User $user): bool
    {
        $currentTime = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE users SET last_login = :lastLogin WHERE id = :id');
        $stmt->bindValue(':lastLogin', $currentTime);
        $stmt->bindValue(':id', $user->id, PDO::PARAM_INT);

        $result = $stmt->execute();
        if ($result) {
            $user->lastLoginDate = $currentTime;
        }
        return $result;
    }

    public function updatePasswordHash(int $userId, string $newPasswordHash, ?string $hashSalt = null): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password = :newPasswordHash, hash_salt = :hashSalt WHERE id = :id'
        );
        $stmt->bindValue(':newPasswordHash', $newPasswordHash);
        $stmt->bindValue(':hashSalt', $hashSalt);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function save(User $user): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (name, prenume, email, password, hash_salt, created_at)
                 VALUES (:name, :prenume, :email, :password, :hash_salt, :created_at)'
            );

            $stmt->bindValue(':name', $user->firstName);
            $stmt->bindValue(':prenume', $user->lastName);
            $stmt->bindValue(':email', $user->email);
            $stmt->bindValue(':password', $user->passwordHash);
            $stmt->bindValue(':hash_salt', $user->hashSalt);
            $stmt->bindValue(':created_at', $user->registrationDate ?? date('Y-m-d H:i:s'));

            $result = $stmt->execute();

            if ($result) {
                $user->id = (int) $this->pdo->lastInsertId();
                return $user->id;
            } else {
                error_log('Eroare la execuția INSERT în UserRepository: ' . implode(' ', $stmt->errorInfo()));
                return false;
            }
        } catch (PDOException $e) {
            error_log('Eroare PDO la salvarea utilizatorului în UserRepository: ' . $e->getMessage());
            return false;
        }
    }
}
