<?php

use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    private AuthService $service;
    private UserRepository $userRepository;
    private ?User $testUser = null;
    private string $testPassword = 'password123';
    private PDO $pdo;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();

        $this->pdo = Database::getConnection();
        $this->userRepository = new UserRepository($this->pdo);
        $this->service = new AuthService($this->userRepository);

        $this->pdo->exec("DELETE FROM users WHERE email = 'test@example.com'");

        $salt = bin2hex(random_bytes(32));
        $hashedPassword = hash('sha256', $salt . $this->testPassword);

        $user = new User(null, 'Test', 'User', 'test@example.com', $hashedPassword, $salt, date('Y-m-d H:i:s'));

        $userId = $this->userRepository->save($user);
        if ($userId) {
            $this->testUser = $this->userRepository->findById($userId);
        }
    }

    public function testLoginSuccess(): void
    {
        $user = $this->service->login('test@example.com', $this->testPassword);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->testUser->id, $user->id);

        $updatedUser = $this->userRepository->findById($this->testUser->id);
        $this->assertNotNull($updatedUser->lastLoginDate);
    }

    public function testLoginWrongPassword(): void
    {
        $user = $this->service->login('test@example.com', 'wrongpassword');
        $this->assertNull($user);
    }

    public function testLoginUserNotFound(): void
    {
        $user = $this->service->login('nonexistent@example.com', 'password');
        $this->assertNull($user);
    }

    public function testIsAuthenticated(): void
    {
        $this->assertFalse($this->service->isAuthenticated());

        $_SESSION['user_id'] = $this->testUser->id;

        $this->assertTrue($this->service->isAuthenticated());
    }

    public function testGetCurrentUser(): void
    {
        $_SESSION['user_id'] = $this->testUser->id;

        $currentUser = $this->service->getCurrentUser();
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertEquals($this->testUser->id, $currentUser->id);
    }

    public function testLogout(): void
    {
        $_SESSION['user_id'] = $this->testUser->id;

        $this->assertTrue($this->service->isAuthenticated());

        $this->service->logout();

        $this->assertFalse($this->service->isAuthenticated());
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    protected function tearDown(): void
    {
        if ($this->testUser && $this->testUser->id) {
            $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->bindValue(':id', $this->testUser->id);
            $stmt->execute();
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        parent::tearDown();
    }
}
