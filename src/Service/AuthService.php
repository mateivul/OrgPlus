<?php

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
            if ($user->verifyPassword($password)) {
                $this->userRepository->updateLastLogin($user);
                return $user;
            }
        }
        return null;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
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
