<?php

namespace App\Core;

/**
 * TwoFactorAuth
 * Gestion de l'authentification à deux facteurs (2FA)
 * Utilise TOTP (Time-based One-Time Password) comme Google Authenticator
 */
class TwoFactorAuth
{
    private const DIGITS = 6;
    private const PERIOD = 30; // 30 secondes
    private const ALGORITHM = 'sha1';

    /**
     * Générer un secret pour le 2FA (base32)
     */
    public static function generateSecret(int $length = 16): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32
        $secret = '';
        
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $secret;
    }

    /**
     * Générer un code TOTP à partir d'un secret
     */
    public static function generateCode(string $secret, ?int $timestamp = null): string
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        // Calculer le compteur basé sur le temps
        $counter = floor($timestamp / self::PERIOD);
        
        // Décoder le secret base32
        $key = self::base32Decode($secret);
        
        // Générer le HMAC
        $time = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac(self::ALGORITHM, $time, $key, true);
        
        // Extraire le code dynamique
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $truncated = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
        
        // Générer le code à 6 chiffres
        $code = str_pad($truncated % pow(10, self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
        
        return $code;
    }

    /**
     * Vérifier un code 2FA
     * Accepte les codes de la période actuelle et des périodes adjacentes (pour drift)
     */
    public static function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $timestamp = time();
        
        // Vérifier le code actuel et les codes adjacents
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $testTime = $timestamp + ($i * self::PERIOD);
            $testCode = self::generateCode($secret, $testTime);
            
            if (hash_equals($testCode, $code)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Générer une URL QR code pour Google Authenticator
     */
    public static function getQRCodeUrl(string $secret, string $email, string $issuer = 'Portfolio'): string
    {
        $otpauthUrl = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=%s&digits=%d&period=%d',
            urlencode($issuer),
            urlencode($email),
            $secret,
            urlencode($issuer),
            strtoupper(self::ALGORITHM),
            self::DIGITS,
            self::PERIOD
        );

        // Utiliser l'API Google Charts pour générer le QR code
        return sprintf(
            'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=%s',
            urlencode($otpauthUrl)
        );
    }

    /**
     * Décoder une chaîne Base32
     */
    private static function base32Decode(string $input): string
    {
        $input = strtoupper($input);
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            $value = strpos($map, $char);
            
            if ($value === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $output .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
                $bitsLeft -= 8;
            }
        }

        return $output;
    }

    /**
     * Générer des codes de backup (pour récupération si perte d'accès)
     */
    public static function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            // Générer un code à 8 caractères (format: XXXX-XXXX)
            $code = sprintf(
                '%04d-%04d',
                random_int(0, 9999),
                random_int(0, 9999)
            );
            $codes[] = $code;
        }
        
        return $codes;
    }

    /**
     * Hasher un code de backup pour stockage sécurisé
     */
    public static function hashBackupCode(string $code): string
    {
        return password_hash($code, PASSWORD_BCRYPT);
    }

    /**
     * Vérifier un code de backup
     */
    public static function verifyBackupCode(string $code, string $hash): bool
    {
        return password_verify($code, $hash);
    }
}
