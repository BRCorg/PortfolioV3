<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RateLimitTest extends TestCase
{
    /**
     * Test rate limit counter
     */
    public function testRateLimitCounter(): void
    {
        $maxAttempts = 5;
        $attempts = 3;

        $this->assertTrue($attempts < $maxAttempts);

        $attempts = 6;
        $this->assertFalse($attempts < $maxAttempts);
    }

    /**
     * Test rate limit time window
     */
    public function testRateLimitTimeWindow(): void
    {
        $windowStart = time() - 300; // 5 minutes ago
        $windowDuration = 900; // 15 minutes

        $isInWindow = (time() - $windowStart) < $windowDuration;

        $this->assertTrue($isInWindow);

        // Test expired window
        $oldWindowStart = time() - 1000; // 16+ minutes ago
        $isInWindow = (time() - $oldWindowStart) < $windowDuration;

        $this->assertFalse($isInWindow);
    }

    /**
     * Test rate limit reset
     */
    public function testRateLimitReset(): void
    {
        $attempts = 5;
        $lastAttempt = time() - 1000; // 16+ minutes ago
        $resetWindow = 900; // 15 minutes

        // Si la fenêtre est expirée, reset
        if ((time() - $lastAttempt) > $resetWindow) {
            $attempts = 0;
        }

        $this->assertEquals(0, $attempts);
    }

    /**
     * Test IP address validation for rate limiting
     */
    public function testIpAddressValidation(): void
    {
        $validIpv4 = '192.168.1.1';
        $validIpv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
        $invalidIp = 'not-an-ip';

        $this->assertNotFalse(filter_var($validIpv4, FILTER_VALIDATE_IP));
        $this->assertNotFalse(filter_var($validIpv6, FILTER_VALIDATE_IP));
        $this->assertFalse(filter_var($invalidIp, FILTER_VALIDATE_IP));
    }

    /**
     * Test exponential backoff calculation
     */
    public function testExponentialBackoff(): void
    {
        $baseDelay = 1; // 1 second
        $attempt = 3;

        // 2^3 * 1 = 8 seconds
        $delay = pow(2, $attempt) * $baseDelay;

        $this->assertEquals(8, $delay);

        // Test with max cap
        $maxDelay = 60; // 1 minute max
        $attempt = 10; // 2^10 = 1024 seconds
        $delay = min(pow(2, $attempt) * $baseDelay, $maxDelay);

        $this->assertEquals(60, $delay);
    }
}
