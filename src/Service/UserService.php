<?php

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function registerUser(string $firstName, string $lastName, string $email, string $password): ?User
    {
        if ($this->userRepository->findByEmail($email)) {
            return null;
        }

        $salt = bin2hex(random_bytes(32));
        $hashedPassword = hash('sha256', $salt . $password);
        if ($hashedPassword === false) {
            return null;
        }

        $currentTime = date('Y-m-d H:i:s');
        $newUser = new User(null, $firstName, $lastName, $email, $hashedPassword, $salt, $currentTime, null);

        if ($this->userRepository->save($newUser)) {
            return $newUser;
        }
        return null;
    }
}
