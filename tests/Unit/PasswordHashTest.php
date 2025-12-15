<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PasswordHashTest extends TestCase
{
    /**
     * Test password hashing
     */
    public function testPasswordHashIsCreatedCorrectly(): void
    {
        $password = 'SecurePassword123!';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->assertNotNull($hash);
        $this->assertNotEquals($password, $hash);
        $this->assertTrue(strlen($hash) === 60); // bcrypt hash length
    }

    /**
     * Test password verification
     */
    public function testPasswordVerificationWorks(): void
    {
        $password = 'MySecretPassword123';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('WrongPassword', $hash));
    }

    /**
     * Test different passwords produce different hashes
     */
    public function testDifferentPasswordsProduceDifferentHashes(): void
    {
        $password1 = 'Password123';
        $password2 = 'Password456';

        $hash1 = password_hash($password1, PASSWORD_BCRYPT);
        $hash2 = password_hash($password2, PASSWORD_BCRYPT);

        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * Test same password produces different hashes (salt)
     */
    public function testSamePasswordProducesDifferentHashesDueToSalt(): void
    {
        $password = 'SamePassword123';

        $hash1 = password_hash($password, PASSWORD_BCRYPT);
        $hash2 = password_hash($password, PASSWORD_BCRYPT);

        $this->assertNotEquals($hash1, $hash2);
        $this->assertTrue(password_verify($password, $hash1));
        $this->assertTrue(password_verify($password, $hash2));
    }
}
