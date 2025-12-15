<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    /**
     * Test email validation
     */
    public function testValidEmailReturnsTrue(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'test+tag@example.com',
            'user123@test-domain.com'
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email '$email' should be valid"
            );
        }
    }

    /**
     * Test invalid email validation
     */
    public function testInvalidEmailReturnsFalse(): void
    {
        $invalidEmails = [
            'notanemail',
            '@example.com',
            'user@',
            'user name@example.com',
            'user@.com'
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email '$email' should be invalid"
            );
        }
    }

    /**
     * Test string length validation
     */
    public function testStringLengthValidation(): void
    {
        $shortString = 'test';
        $longString = str_repeat('a', 256);

        $this->assertTrue(strlen($shortString) >= 3 && strlen($shortString) <= 100);
        $this->assertFalse(strlen($longString) <= 100);
    }

    /**
     * Test required field validation
     */
    public function testRequiredFieldValidation(): void
    {
        $emptyString = '';
        $whitespaceString = '   ';
        $validString = 'test';

        $this->assertFalse(!empty(trim($emptyString)));
        $this->assertFalse(!empty(trim($whitespaceString)));
        $this->assertTrue(!empty(trim($validString)));
    }
}
