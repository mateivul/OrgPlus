<?php

// namespace App\Service;

// use App\Model\User;
// use App\Repository\UserRepository;

class AuthService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login(string $email, string $password): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user) {
            echo 'DEBUG AuthService: Utilizator găsit. Încercare de verificare parolă...<br>';
            if ($user->verifyPassword($password)) {
                echo 'DEBUG AuthService: Parolă verificată cu succes.<br>';

                // --- Logica de migrare a parolei ---
                $info = password_get_info($user->passwordHash);
                echo 'DEBUG AuthService: Password info algo: ' .
                    $info['algo'] .
                    ', oldSalt: ' .
                    ($user->oldSalt ?? 'NULL') .
                    '<br>';

                if ($info['algo'] === 0 && $user->oldSalt !== null) {
                    echo 'DEBUG AuthService: Detectat hash vechi. Încercare de migrare...<br>';
                    $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
                    if ($this->userRepository->updatePasswordHash($user->id, $newPasswordHash)) {
                        echo 'DEBUG AuthService: Parolă migrată cu succes.<br>';
                        $user->passwordHash = $newPasswordHash;
                        $user->oldSalt = null;
                    } else {
                        echo 'DEBUG AuthService EROARE: Migrare parolă eșuată în UserRepository.<br>';
                    }
                }
                // ... restul logicii de login
                return $user;
            } else {
                echo 'DEBUG AuthService: Verificare parolă eșuată.<br>';
            }
        } else {
            echo "DEBUG AuthService: Utilizatorul cu email-ul '$email' nu a fost găsit.<br>";
        }
        return null;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        // Șterge cookie-ul de sesiune
        setcookie(session_name(), '', time() - 3600, '/'); // Asigură ștergerea cookie-ului
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser(): ?User
    {
        if ($this->isAuthenticated()) {
            return $this->userRepository->findById($_SESSION['user_id']);
        }
        return null;
    }
}
