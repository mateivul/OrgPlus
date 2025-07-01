<?php

use PHPUnit\Framework\TestCase;

class CsrfTokenTest extends TestCase
{
    protected function setUp(): void
    {
        // Start with a clean session for each test
        $_SESSION = [];
    }

    public function testGenerateTokenCreatesNewToken(): void
    {
        $token = CsrfToken::generateToken();
        $this->assertNotEmpty($token);
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    public function testGenerateTokenReturnsExistingToken(): void
    {
        $existingToken = 'my_test_token';
        $_SESSION['csrf_token'] = $existingToken;

        $token = CsrfToken::generateToken();
        $this->assertEquals($existingToken, $token);
    }

    public function testValidateTokenSuccess(): void
    {
        $token = CsrfToken::generateToken();
        $this->assertTrue(CsrfToken::validateToken($token));
    }

    public function testValidateTokenFailure(): void
    {
        CsrfToken::generateToken();
        $this->assertFalse(CsrfToken::validateToken('invalid_token'));
    }

    public function testValidateTokenWhenNoTokenInSession(): void
    {
        $this->assertFalse(CsrfToken::validateToken('any_token'));
    }

    public function testCsrfField(): void
    {
        $token = CsrfToken::generateToken();
        $expectedField = '<input type="hidden" name="csrf_token" value="' . $token . '">';
        $this->assertEquals($expectedField, CsrfToken::csrfField());
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        $_SESSION = [];
    }
}
