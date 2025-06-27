<?php

// namespace App\Service;

// use App\Model\User;
// use App\Repository\UserRepository;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Înregistrează un utilizator nou.
     *
     * @param string $firstName Numele utilizatorului.
     * @param string $lastName Prenumele utilizatorului.
     * @param string $email Email-ul utilizatorului.
     * @param string $password Parola în clar a utilizatorului.
     * @return User|null Obiectul User înregistrat cu succes sau null în caz de eșec.
     */
    public function registerUser(string $firstName, string $lastName, string $email, string $password): ?User
    {
        if ($this->userRepository->findByEmail($email)) {
            return null; // Email-ul este deja înregistrat
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($hashedPassword === false) {
            return null;
        }

        $currentTime = date('Y-m-d H:i:s');
        $newUser = new User(null, $firstName, $lastName, $email, $hashedPassword, null, $currentTime, null);

        if ($this->userRepository->save($newUser)) {
            return $newUser;
        }
        return null;
    }
}
