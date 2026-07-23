<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(
        ?User $actor,
        string $action,
        ?Model $subject = null,
        array $before = [],
        array $after = [],
        array $metadata = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'branch_id' => $metadata['branch_id'] ?? $subject?->branch_id ?? $actor?->branch_id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'before' => $this->redact($before),
            'after' => $this->redact($after),
            'metadata' => $this->redact($metadata),
            'ip_address' => request()?->ip(),
            'user_agent' => str(request()?->userAgent())->limit(255)->toString(),
        ]);
    }

    private function redact(array $payload): array
    {
        $blockedKeys = [
            'password',
            'password_confirmation',
            'activation_pin',
            'activation_pin_hash',
            'activation_pin_encrypted',
            'pin',
            'token',
            'access_token',
            'fcm_token',
            'nik',
            'passport_number',
        ];

        foreach ($payload as $key => $value) {
            if (in_array((string) $key, $blockedKeys, true)) {
                $payload[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $payload[$key] = $this->redact($value);
            }
        }

        return $payload;
    }
}
