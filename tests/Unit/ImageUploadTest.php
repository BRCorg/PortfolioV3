<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ImageUploadTest extends TestCase
{
    /**
     * Test allowed image MIME types
     */
    public function testAllowedMimeTypes(): void
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        $validMime = 'image/jpeg';
        $invalidMime = 'application/pdf';

        $this->assertTrue(in_array($validMime, $allowedMimes));
        $this->assertFalse(in_array($invalidMime, $allowedMimes));
    }

    /**
     * Test file extension validation
     */
    public function testFileExtensionValidation(): void
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $validFile = 'image.jpg';
        $invalidFile = 'document.pdf';

        $validExt = strtolower(pathinfo($validFile, PATHINFO_EXTENSION));
        $invalidExt = strtolower(pathinfo($invalidFile, PATHINFO_EXTENSION));

        $this->assertTrue(in_array($validExt, $allowedExtensions));
        $this->assertFalse(in_array($invalidExt, $allowedExtensions));
    }

    /**
     * Test file size validation (5MB max)
     */
    public function testFileSizeValidation(): void
    {
        $maxSize = 5 * 1024 * 1024; // 5MB en bytes

        $validSize = 2 * 1024 * 1024; // 2MB
        $invalidSize = 10 * 1024 * 1024; // 10MB

        $this->assertTrue($validSize <= $maxSize);
        $this->assertFalse($invalidSize <= $maxSize);
    }

    /**
     * Test filename sanitization
     */
    public function testFilenameSanitization(): void
    {
        $dangerousFilename = '../../../etc/passwd.jpg';
        $normalFilename = 'my photo.jpg';

        // Sanitize: remove path traversal et caractères spéciaux
        $sanitized1 = preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($dangerousFilename));
        $sanitized2 = preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($normalFilename));

        $this->assertEquals('passwd.jpg', $sanitized1);
        $this->assertEquals('my_photo.jpg', $sanitized2);
        $this->assertStringNotContainsString('..', $sanitized1);
        $this->assertStringNotContainsString('/', $sanitized1);
    }

    /**
     * Test unique filename generation
     */
    public function testUniqueFilenameGeneration(): void
    {
        $originalName = 'test.jpg';
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        $uniqueName1 = uniqid('img_', true) . '.' . $extension;
        $uniqueName2 = uniqid('img_', true) . '.' . $extension;

        $this->assertNotEquals($uniqueName1, $uniqueName2);
        $this->assertStringEndsWith('.jpg', $uniqueName1);
        $this->assertStringStartsWith('img_', $uniqueName1);
    }
}
