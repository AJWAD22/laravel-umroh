<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Crypt;

trait ProtectsSensitiveIdentity
{
    public function setNikAttribute($value): void
    {
        $normalized = $this->normalizeSensitiveValue($value);

        $this->attributes['nik'] = $normalized ? Crypt::encryptString($normalized) : null;
        $this->attributes['nik_hash'] = $normalized ? self::identityDigest($normalized) : null;
    }

    public function getNikAttribute($value): ?string
    {
        return $this->decryptSensitiveValue($value);
    }

    public function setPassportNumberAttribute($value): void
    {
        $normalized = $this->normalizeSensitiveValue($value);

        $this->attributes['passport_number'] = $normalized ? Crypt::encryptString($normalized) : null;
        $this->attributes['passport_number_hash'] = $normalized ? self::identityDigest($normalized) : null;
    }

    public function getPassportNumberAttribute($value): ?string
    {
        return $this->decryptSensitiveValue($value);
    }

    public function maskedNik(): string
    {
        return $this->maskSensitive($this->nik, 4);
    }

    public function maskedPassportNumber(): string
    {
        return $this->maskSensitive($this->passport_number, 3);
    }

    public static function identityDigest(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === ''
            ? null
            : hash_hmac('sha256', $normalized, (string) config('app.key'));
    }

    private function normalizeSensitiveValue($value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function decryptSensitiveValue($value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    private function maskSensitive(?string $value, int $visibleTail): string
    {
        if (! filled($value)) {
            return '-';
        }

        $length = strlen($value);
        $tail = substr($value, max(0, $length - $visibleTail));

        return str_repeat('*', max(4, $length - $visibleTail)).$tail;
    }
}
