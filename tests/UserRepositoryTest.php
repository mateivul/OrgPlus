<?php

use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        $pdo = Database::getConnection();
        $this->repository = new UserRepository($pdo);
    }

    public function testFindByEmailReturnsUser(): void
    {
        $user = $this->repository->findByEmail('test_user1@gmail.com');
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('test_user1@gmail.com', $user->email);
    }
}
