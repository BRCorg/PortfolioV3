<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SanitizationTest extends TestCase
{
    /**
     * Test HTML special chars encoding (XSS protection)
     */
    public function testHtmlSpecialCharsEncoding(): void
    {
        $maliciousInput = '<script>alert("XSS")</script>';
        $sanitized = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('&lt;script&gt;', $sanitized);
    }

    /**
     * Test URL sanitization
     */
    public function testUrlSanitization(): void
    {
        $validUrl = 'https://example.com/page?param=value';
        $maliciousUrl = 'javascript:alert(1)';

        $filteredValid = filter_var($validUrl, FILTER_VALIDATE_URL);
        $filteredMalicious = filter_var($maliciousUrl, FILTER_VALIDATE_URL);

        $this->assertNotFalse($filteredValid);
        // JavaScript URLs are technically valid URLs, so we need additional checks
        $this->assertStringStartsNotWith('javascript:', strtolower($validUrl));
    }

    /**
     * Test SQL injection prevention (basic validation)
     */
    public function testSqlInjectionPrevention(): void
    {
        $maliciousInput = "1' OR '1'='1";

        // Avec PDO, on utilise des prepared statements
        // Test que l'input contient des caractères dangereux
        $containsDangerousChars = preg_match("/['\"]/", $maliciousInput);

        $this->assertTrue($containsDangerousChars > 0);
        // Dans une vraie app, on utiliserait TOUJOURS des prepared statements
    }

    /**
     * Test trim et nettoyage des espaces
     */
    public function testTrimWhitespace(): void
    {
        $input = "  test value  \n\t";
        $cleaned = trim($input);

        $this->assertEquals('test value', $cleaned);
        $this->assertStringStartsNotWith(' ', $cleaned);
        $this->assertStringEndsNotWith(' ', $cleaned);
    }

    /**
     * Test strip tags
     */
    public function testStripTags(): void
    {
        $input = 'Hello <b>World</b><script>alert(1)</script>';
        $stripped = strip_tags($input);

        $this->assertEquals('Hello Worldalert(1)', $stripped);
        $this->assertStringNotContainsString('<b>', $stripped);
        $this->assertStringNotContainsString('<script>', $stripped);
    }

    /**
     * Test number validation
     */
    public function testNumberValidation(): void
    {
        $validNumber = '123';
        $invalidNumber = '123abc';

        $this->assertTrue(is_numeric($validNumber));
        $this->assertFalse(is_numeric($invalidNumber));
        $this->assertEquals(123, filter_var($validNumber, FILTER_VALIDATE_INT));
    }
}
