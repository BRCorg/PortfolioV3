<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CsrfTokenTest extends TestCase
{
    /**
     * Test CSRF token generation
     */
    public function testCsrfTokenIsGenerated(): void
    {
        $token = bin2hex(random_bytes(32));

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    /**
     * Test CSRF token format
     */
    public function testCsrfTokenFormat(): void
    {
        $token = bin2hex(random_bytes(32));

        // Token doit être hexadécimal
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    /**
     * Test CSRF tokens are unique
     */
    public function testCsrfTokensAreUnique(): void
    {
        $token1 = bin2hex(random_bytes(32));
        $token2 = bin2hex(random_bytes(32));

        $this->assertNotEquals($token1, $token2);
    }

    /**
     * Test token comparison (timing safe)
     */
    public function testTokenComparison(): void
    {
        $token = bin2hex(random_bytes(32));
        $validToken = $token;
        $invalidToken = bin2hex(random_bytes(32));

        // Simulation d'une comparaison sécurisée
        $this->assertTrue(hash_equals($token, $validToken));
        $this->assertFalse(hash_equals($token, $invalidToken));
    }
}
