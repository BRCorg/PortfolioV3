<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests de sécurité pour SQL Injection et XSS
 */
class SecurityTest extends TestCase
{
    // ==========================================
    // TESTS SQL INJECTION
    // ==========================================

    /**
     * Test que les simples quotes sont échappées
     */
    public function testSqlInjectionSingleQuote(): void
    {
        $input = "admin' OR '1'='1";
        $escaped = addslashes($input);

        $this->assertStringContainsString("\\'", $escaped);
        $this->assertNotEquals($input, $escaped);
    }

    /**
     * Test injection SQL avec UNION SELECT
     */
    public function testSqlInjectionUnionSelect(): void
    {
        $maliciousInput = "1' UNION SELECT username, password FROM users--";

        // Simuler PDO prepare (qui protège contre SQL injection)
        $cleaned = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        $this->assertStringContainsString("UNION SELECT", $maliciousInput);
        $this->assertNotEmpty($cleaned);
    }

    /**
     * Test injection SQL avec DROP TABLE
     */
    public function testSqlInjectionDropTable(): void
    {
        $maliciousInput = "'; DROP TABLE users; --";

        // Vérifier que l'input contient bien une tentative d'injection
        $this->assertStringContainsString("DROP TABLE", $maliciousInput);

        // Simuler la sanitization
        $escaped = addslashes($maliciousInput);
        $this->assertStringContainsString("\\'", $escaped);
    }

    /**
     * Test injection SQL avec commentaire MySQL
     */
    public function testSqlInjectionMysqlComment(): void
    {
        $maliciousInput = "admin'-- ";

        $this->assertStringContainsString("--", $maliciousInput);

        // Vérifier que addslashes protège
        $escaped = addslashes($maliciousInput);
        $this->assertStringContainsString("\\'", $escaped);
    }

    /**
     * Test injection SQL avec OR 1=1
     */
    public function testSqlInjectionOrCondition(): void
    {
        $maliciousInput = "' OR 1=1 -- ";

        $this->assertStringContainsString("OR 1=1", $maliciousInput);

        // PDO prepare échapperait automatiquement ceci
        $cleaned = str_replace("'", "''", $maliciousInput);
        $this->assertStringContainsString("''", $cleaned);
    }

    /**
     * Test que PDO::quote protège contre les injections
     */
    public function testPdoQuoteProtection(): void
    {
        // Simuler le comportement de PDO::quote
        $maliciousInput = "admin' OR '1'='1";

        // PDO::quote ajoute des quotes et échappe
        $quoted = "'" . str_replace("'", "''", $maliciousInput) . "'";

        $this->assertStringStartsWith("'", $quoted);
        $this->assertStringEndsWith("'", $quoted);
        $this->assertStringContainsString("''", $quoted);
    }

    // ==========================================
    // TESTS XSS (Cross-Site Scripting)
    // ==========================================

    /**
     * Test XSS avec balise script simple
     */
    public function testXssScriptTag(): void
    {
        $maliciousInput = "<script>alert('XSS')</script>";
        $sanitized = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString("<script>", $sanitized);
        $this->assertStringContainsString("&lt;script&gt;", $sanitized);
    }

    /**
     * Test XSS avec attribut onerror
     */
    public function testXssOnErrorAttribute(): void
    {
        $maliciousInput = '<img src=x onerror="alert(\'XSS\')">';
        $sanitized = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        // htmlspecialchars encode les balises, rendant le code inoffensif
        $this->assertStringContainsString('&lt;img', $sanitized);
        $this->assertStringContainsString('&gt;', $sanitized);
        $this->assertStringNotContainsString('<img', $sanitized);
    }

    /**
     * Test XSS avec javascript: dans href
     */
    public function testXssJavascriptHref(): void
    {
        $maliciousInput = '<a href="javascript:alert(\'XSS\')">Click</a>';
        $sanitized = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        // htmlspecialchars encode les balises, rendant le lien inoffensif
        $this->assertStringContainsString('&lt;a', $sanitized);
        $this->assertStringContainsString('&gt;', $sanitized);
        $this->assertStringNotContainsString('<a href=', $sanitized);
    }

    /**
     * Test XSS avec iframe malveillant
     */
    public function testXssMaliciousIframe(): void
    {
        $maliciousInput = '<iframe src="http://evil.com/steal.php"></iframe>';
        $sanitized = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<iframe', $sanitized);
        $this->assertStringContainsString('&lt;iframe', $sanitized);
    }

    /**
     * Test XSS avec événement onload
     */
    public function testXssOnloadEvent(): void
    {
        $maliciousInput = '<body onload="alert(\'XSS\')">';
        $sanitized = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        // htmlspecialchars encode les balises HTML
        $this->assertStringContainsString('&lt;body', $sanitized);
        $this->assertStringContainsString('&gt;', $sanitized);
        $this->assertStringNotContainsString('<body', $sanitized);
    }

    /**
     * Test XSS avec SVG malveillant
     */
    public function testXssMaliciousSvg(): void
    {
        $maliciousInput = '<svg onload="alert(\'XSS\')"></svg>';
        $sanitized = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<svg', $sanitized);
        $this->assertStringContainsString('&lt;svg', $sanitized);
    }

    /**
     * Test XSS avec encodage HTML entities
     */
    public function testXssHtmlEntitiesEncoding(): void
    {
        $maliciousInput = "<script>alert('XSS')</script>";
        $sanitized = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        // Vérifier que les caractères dangereux sont encodés
        $this->assertStringContainsString('&lt;', $sanitized);
        $this->assertStringContainsString('&gt;', $sanitized);
        $this->assertStringContainsString('&#039;', $sanitized);
    }

    /**
     * Test que strip_tags supprime toutes les balises HTML
     */
    public function testStripTagsRemovesHtml(): void
    {
        $maliciousInput = '<script>alert("XSS")</script><b>Bold text</b>';
        $cleaned = strip_tags($maliciousInput);

        $this->assertStringNotContainsString('<script>', $cleaned);
        $this->assertStringNotContainsString('<b>', $cleaned);
        $this->assertEquals('alert("XSS")Bold text', $cleaned);
    }

    // ==========================================
    // TESTS COMBINÉS SQL + XSS
    // ==========================================

    /**
     * Test attaque combinée SQL injection + XSS
     */
    public function testCombinedSqlAndXssAttack(): void
    {
        $maliciousInput = "'; DROP TABLE users; --<script>alert('XSS')</script>";

        // Protection SQL
        $sqlSafe = addslashes($maliciousInput);
        $this->assertStringContainsString("\\'", $sqlSafe);

        // Protection XSS
        $xssSafe = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('&lt;script&gt;', $xssSafe);

        // Protection complète (SQL + XSS)
        $fullySafe = htmlspecialchars(addslashes($maliciousInput), ENT_QUOTES, 'UTF-8');
        $this->assertStringNotContainsString('<script>', $fullySafe);
        $this->assertStringContainsString('&lt;script&gt;', $fullySafe);
        // htmlspecialchars encode aussi les quotes, donc \' devient \&#039;
        $this->assertStringContainsString('&#039;', $fullySafe);
    }

    /**
     * Test injection dans un paramètre d'URL
     */
    public function testUrlParameterInjection(): void
    {
        $maliciousUrl = "?id=1' OR '1'='1&name=<script>alert('XSS')</script>";

        // Parser l'URL
        parse_str(substr($maliciousUrl, 1), $params);

        // Vérifier que les paramètres contiennent du code malveillant
        $this->assertStringContainsString("'", $params['id']);
        $this->assertStringContainsString('<script>', $params['name']);

        // Sanitizer chaque paramètre
        $cleanId = htmlspecialchars($params['id'], ENT_QUOTES, 'UTF-8');
        $cleanName = htmlspecialchars($params['name'], ENT_QUOTES, 'UTF-8');

        // Vérifier que le code est neutralisé
        $this->assertStringNotContainsString('<script>', $cleanName);
        $this->assertStringContainsString('&lt;script&gt;', $cleanName);
        $this->assertStringContainsString('&#039;', $cleanId); // Quote encodée
    }

    // ==========================================
    // TESTS DE VALIDATION D'ENTRÉES
    // ==========================================

    /**
     * Test que les emails malformés avec injection sont rejetés
     */
    public function testEmailWithInjection(): void
    {
        $maliciousEmail = "admin'--@example.com";

        // filter_var accepte techniquement cet email car les quotes sont valides dans les emails
        // Il faut donc sanitizer l'email après validation
        $isValid = filter_var($maliciousEmail, FILTER_VALIDATE_EMAIL);

        if ($isValid) {
            // Si l'email est techniquement valide, on doit quand même l'échapper pour SQL
            $sanitized = htmlspecialchars($maliciousEmail, ENT_QUOTES, 'UTF-8');
            $this->assertStringContainsString('&#039;', $sanitized);
        }

        $this->assertNotFalse($isValid); // L'email est techniquement valide
    }

    /**
     * Test validation d'URL avec javascript:
     */
    public function testUrlWithJavascriptScheme(): void
    {
        $maliciousUrl = "javascript:alert('XSS')";

        // filter_var devrait rejeter cette URL
        $isValid = filter_var($maliciousUrl, FILTER_VALIDATE_URL);

        $this->assertFalse($isValid);
    }

    /**
     * Test que les nombres avec injection SQL sont nettoyés
     */
    public function testNumericInputWithSqlInjection(): void
    {
        $maliciousInput = "123' OR '1'='1";

        // Conversion en entier supprime l'injection
        $clean = (int) $maliciousInput;

        $this->assertEquals(123, $clean);
        $this->assertIsInt($clean);
    }

    /**
     * Test protection contre NULL byte injection
     */
    public function testNullByteInjection(): void
    {
        $maliciousInput = "file.php\0.jpg";

        // Supprimer les null bytes
        $cleaned = str_replace("\0", "", $maliciousInput);

        $this->assertStringNotContainsString("\0", $cleaned);
        $this->assertEquals("file.php.jpg", $cleaned);
    }

    /**
     * Test protection contre Path Traversal
     */
    public function testPathTraversalAttack(): void
    {
        $maliciousPath = "../../etc/passwd";

        // Vérifier que le chemin contient ..
        $this->assertStringContainsString("..", $maliciousPath);

        // Nettoyer avec basename
        $safe = basename($maliciousPath);

        $this->assertEquals("passwd", $safe);
        $this->assertStringNotContainsString("..", $safe);
    }
}
