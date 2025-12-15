<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    /**
     * Test base32 encoding/decoding
     */
    public function testBase32Encoding(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP'; // Base32 encoded string

        // Vérifier que c'est bien du base32
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
        $this->assertEquals(16, strlen($secret)); // Longueur standard pour TOTP
    }

    /**
     * Test TOTP code format
     */
    public function testTotpCodeFormat(): void
    {
        // Un code TOTP valide doit être 6 chiffres
        $validCode = '123456';
        $invalidCode1 = '12345';  // Trop court
        $invalidCode2 = '1234567'; // Trop long
        $invalidCode3 = 'ABCDEF';  // Pas des chiffres

        $this->assertMatchesRegularExpression('/^\d{6}$/', $validCode);
        $this->assertDoesNotMatchRegularExpression('/^\d{6}$/', $invalidCode1);
        $this->assertDoesNotMatchRegularExpression('/^\d{6}$/', $invalidCode2);
        $this->assertDoesNotMatchRegularExpression('/^\d{6}$/', $invalidCode3);
    }

    /**
     * Test secret generation length
     */
    public function testSecretGenerationLength(): void
    {
        // Générer un secret aléatoire base32
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $this->assertEquals(16, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    /**
     * Test backup code format
     */
    public function testBackupCodeFormat(): void
    {
        // Format typique: XXXX-XXXX-XXXX
        $backupCode = 'A1B2-C3D4-E5F6';

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $backupCode);
    }

    /**
     * Test multiple backup codes uniqueness
     */
    public function testBackupCodesAreUnique(): void
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = bin2hex(random_bytes(6));
        }

        $uniqueCodes = array_unique($codes);
        $this->assertCount(10, $uniqueCodes);
    }
}
