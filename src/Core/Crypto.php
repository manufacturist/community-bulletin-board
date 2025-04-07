<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Types\Binary;

final class Crypto
{
    private static ?self $instance = null;

    private string $encryptionKey;
    private string $hmacKey;
    private string $pepper;

    /**
     * @throws \Exception
     */
    private function __construct()
    {
        $envEncryptionKey = $_ENV['CRYPTO_ENCRYPTION_KEY'];
        $envHmacKey = $_ENV['CRYPTO_HMAC_KEY'];
        $envPepper = $_ENV['CRYPTO_PEPPER'];

        $encryptionKey = is_string($envEncryptionKey) ? base64_decode($envEncryptionKey) : null;
        $hmacKey = is_string($envHmacKey) ? base64_decode($envHmacKey) : null;
        $pepper = is_string($envPepper) ? base64_decode($envPepper) : null;

        if (is_null($encryptionKey)) {
            throw new \Exception('Secret key is not set.');
        }

        if (is_null($hmacKey)) {
            throw new \Exception('Secret key is not set.');
        }

        if (is_null($pepper)) {
            throw new \Exception('Pepper is not set.');
        }

        $this->encryptionKey = $encryptionKey;
        $this->hmacKey = $hmacKey;
        $this->pepper = $pepper;
    }

    private static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @throws \Exception
     */
    public static function encrypt(string $data): Binary
    {
        $iv = openssl_random_pseudo_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            data: $data,
            cipher_algo: 'aes-128-gcm',
            passphrase: self::getInstance()->encryptionKey,
            options: OPENSSL_RAW_DATA,
            iv: $iv,
            tag: $tag,
        );

        if ($ciphertext === false) {
            throw new \Exception('Unable to encrypt data.');
        }

        return Binary::apply($iv . $ciphertext . $tag);
    }

    /**
     * @throws \Exception
     */
    public static function decrypt(Binary $encrypted): string
    {
        $encryptedValue = $encrypted->value;

        $iv = substr($encryptedValue, 0, 12);
        $ciphertext = substr($encryptedValue, 12, -16);
        $tag = substr($encryptedValue, -16);

        $decryptResult = openssl_decrypt(
            data: $ciphertext,
            cipher_algo: 'aes-128-gcm',
            passphrase: self::getInstance()->encryptionKey,
            options: OPENSSL_RAW_DATA,
            iv: $iv,
            tag: $tag
        );

        if ($decryptResult === false) {
            throw new \Exception('Failed to read sensitive data.');
        }

        return $decryptResult;
    }

    public static function hash(string $data): Binary
    {
        $hash = hash_hmac(
            algo: 'sha256',
            data: $data . self::getInstance()->pepper,
            key: self::getInstance()->hmacKey,
            binary: true
        );

        return Binary::apply($hash);
    }
}
