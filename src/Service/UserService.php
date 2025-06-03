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
        echo "DEBUG: Încercare înregistrare user: $email<br>"; // Debugging
        // 1. Verificăm dacă email-ul există deja
        if ($this->userRepository->findByEmail($email)) {
            echo "DEBUG: Email-ul '$email' este deja înregistrat.<br>"; // Debugging
            return null; // Email-ul este deja înregistrat
        }
        echo "DEBUG: Email '$email' disponibil.<br>"; // Debugging

        // 2. Hashuim parola
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($hashedPassword === false) {
            echo 'DEBUG EROARE: Eșec la hashing-ul parolei.<br>'; // Debugging
            return null;
        }
        echo 'DEBUG: Parola a fost hash-uită.<br>'; // Debugging

        // 3. Creăm un obiect User
        $currentTime = date('Y-m-d H:i:s');
        $newUser = new User(null, $firstName, $lastName, $email, $hashedPassword, null, $currentTime, null);
        echo 'DEBUG: Obiect User creat.<br>'; // Debugging

        // 4. Încercăm să salvăm utilizatorul în baza de date
        if ($this->userRepository->save($newUser)) {
            echo 'DEBUG: User salvat cu succes în DB.<br>'; // Debugging
            return $newUser;
        }
        echo 'DEBUG EROARE: Salvarea userului în repository a eșuat.<br>'; // Debugging
        return null;
    }
}
