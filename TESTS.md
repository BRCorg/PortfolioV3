# Tests - Portfolio V3

Documentation complète des tests unitaires et de sécurité du projet.

---

## ⚡ Commandes essentielles

### 1. Exécuter tous les tests
```bash
./vendor/bin/phpunit
```

### 2. Afficher les détails (format lisible)
```bash
./vendor/bin/phpunit --testdox
```

### 3. Tester un fichier spécifique
```bash
./vendor/bin/phpunit tests/Unit/SecurityTest.php
```

---

## 📊 Résultats attendus

Lors de l'exécution complète, vous devriez voir :

```
PHPUnit 10.5.58 by Sebastian Bergmann and contributors.

..........................................................            58 / 58 (100%)

Time: 00:03.452, Memory: 8.00 MB

OK (58 tests, 140 assertions)
```

✅ **58 tests** passés
✅ **140 assertions** validées
✅ **0 erreurs**

---

## 📁 Tests disponibles

### 🔐 Tests de sécurité (37 tests)

#### **CSRF Token** - Protection contre les attaques CSRF
- Génération et validation de tokens
- Format et unicité des tokens

#### **Password Hash** - Hashing sécurisé des mots de passe
- Création et vérification de hash bcrypt
- Protection par salt

#### **Sanitization** - Prévention XSS et injections SQL
- Encodage HTML, nettoyage URL
- Suppression de balises et validation

#### **Security Test** - Tests d'injections et attaques (21 tests)
**Injections SQL (6 tests) :**
- ✅ Simple quote (`admin' OR '1'='1`)
- ✅ UNION SELECT, DROP TABLE
- ✅ Commentaires MySQL (`--`)
- ✅ Conditions OR 1=1
- ✅ Protection PDO

**XSS - Cross-Site Scripting (8 tests) :**
- ✅ `<script>`, `onerror`, `onload`
- ✅ `javascript:` dans href
- ✅ iframe, SVG malveillants
- ✅ HTML entities, strip_tags

**Attaques combinées (2 tests) :**
- ✅ SQL + XSS simultanés
- ✅ Injection dans URL

**Validation d'entrées (5 tests) :**
- ✅ Email avec injection
- ✅ URL javascript:
- ✅ NULL byte injection
- ✅ Path Traversal (`../../`)

---

### 🔑 Tests d'authentification (9 tests)

#### **Session** - Gestion sécurisée des sessions
- Format ID, timeout, rôles
- Sanitization des données

#### **TOTP** - Authentification 2FA
- Encodage Base32
- Codes TOTP et backup

---

### 🚦 Tests de protection (5 tests)

#### **Rate Limit** - Anti-spam et force brute
- Compteur de requêtes
- Fenêtre temporelle
- Backoff exponentiel

---

### 📁 Tests d'upload (5 tests)

#### **Image Upload** - Validation des fichiers
- Types MIME, extensions
- Taille, noms sécurisés

---

### ✅ Tests de validation (2 tests)

#### **Validation** - Validation des données
- Emails, longueur, champs requis

---

## 🎯 Écrire de nouveaux tests

### Structure d'un test PHPUnit

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MonNouveauTest extends TestCase
{
    /**
     * @test
     */
    public function mon_test_description(): void
    {
        // Arrange (préparer)
        $valeur = 5;

        // Act (agir)
        $resultat = $valeur * 2;

        // Assert (vérifier)
        $this->assertEquals(10, $resultat);
    }
}
```

### Assertions courantes

```php
// Égalité
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual); // Strict (===)

// Booléens
$this->assertTrue($condition);
$this->assertFalse($condition);

// Null
$this->assertNull($var);
$this->assertNotNull($var);

// Tableaux
$this->assertArrayHasKey('key', $array);
$this->assertContains($needle, $haystack);

// Chaînes
$this->assertStringContainsString('substring', $string);
$this->assertMatchesRegularExpression('/pattern/', $string);

// Exceptions
$this->expectException(Exception::class);
```

### Placer les nouveaux tests

- **Tests unitaires** : `tests/Unit/`
- **Tests d'intégration** : `tests/Integration/` (à créer si nécessaire)
- **Tests fonctionnels** : `tests/Functional/` (à créer si nécessaire)

---

## 💡 Bonnes pratiques

1. **Lancer les tests avant chaque commit**
   ```bash
   ./vendor/bin/phpunit && git commit
   ```

2. **Nommer les tests de manière descriptive**
   - ✅ `testEmailValidationRejectsInvalidFormat`
   - ❌ `testEmail`

3. **Tester les cas limites** : valeurs nulles, chaînes vides, valeurs extrêmes

---

**Développé avec ❤️ pour garantir la qualité et la sécurité du code**
