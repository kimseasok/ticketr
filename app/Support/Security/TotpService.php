<?php

namespace App\Support\Security;

class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;

    public function generateSecret(int $length = 32): string
    {
        $characters = collect(range(1, $length))->map(function (): string {
            return self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        })->implode('');

        return $characters;
    }

    public function generateUri(string $secret, string $email, string $issuer): string
    {
        $label = rawurlencode($issuer.':'.$email);
        $issuer = rawurlencode($issuer);

        return sprintf('otpauth://totp/%s?secret=%s&issuer=%s&period=%d&digits=%d', $label, $secret, $issuer, self::PERIOD, self::DIGITS);
    }

    public function verify(string $secret, string $code, int $window = 1, ?int $timestamp = null): bool
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $time = (int) floor(($timestamp ?? time()) / self::PERIOD);
        $binarySecret = $this->decodeBase32($secret);

        for ($offset = -$window; $offset <= $window; $offset++) {
            $binaryTime = pack('N*', 0).pack('N*', $time + $offset);
            $hash = hash_hmac('sha1', $binaryTime, $binarySecret, true);
            $truncatedHash = unpack('N', substr($hash, ord(substr($hash, -1)) & 0x0F, 4))[1] & 0x7fffffff;
            $generated = str_pad((string) ($truncatedHash % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);

            if (hash_equals($generated, $code)) {
                return true;
            }
        }

        return false;
    }

    public function generateCode(string $secret, ?int $timestamp = null): string
    {
        $time = (int) floor(($timestamp ?? time()) / self::PERIOD);
        $binarySecret = $this->decodeBase32($secret);
        $binaryTime = pack('N*', 0).pack('N*', $time);
        $hash = hash_hmac('sha1', $binaryTime, $binarySecret, true);
        $truncatedHash = unpack('N', substr($hash, ord(substr($hash, -1)) & 0x0F, 4))[1] & 0x7fffffff;

        return str_pad((string) ($truncatedHash % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function decodeBase32(string $secret): string
    {
        $secret = strtoupper($secret);
        $secret = preg_replace('/[^'.self::ALPHABET.']/', '', $secret);

        $binaryString = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (str_split($secret) as $character) {
            $buffer = ($buffer << 5) | strpos(self::ALPHABET, $character);
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binaryString .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $binaryString;
    }
}
