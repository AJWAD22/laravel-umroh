<?php

namespace App\Services;

use App\Models\MobileDevice;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FcmPushService
{
    /**
     * Pengirim notifikasi Firebase Cloud Messaging.
     * Token perangkat diambil dari mobile_devices; jika kredensial Firebase
     * belum tersedia, proses aplikasi tetap berjalan tanpa mengirim push.
     *
     * @param  Collection<int, User>  $users
     * @param  array<string, mixed>  $data
     */
    public function sendToUsers(Collection $users, string $title, string $body, array $data = []): void
    {
        $credentials = $this->credentials();
        if (! $credentials) {
            // FCM bersifat pendukung. Jika file firebase-admin.json belum ada,
            // sistem tetap menyimpan data SOS/notifikasi database seperti biasa.
            return;
        }

        // Hanya perangkat aktif yang punya fcm_token yang bisa dikirimi push.
        // Perangkat lama yang sudah logout/refresh PIN memiliki revoked_at.
        $devices = MobileDevice::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->whereNull('revoked_at')
            ->whereNotNull('fcm_token')
            ->get();

        foreach ($devices as $device) {
            try {
                $response = Http::withToken($this->accessToken($credentials))
                    ->post(
                        "https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send",
                        [
                            'message' => [
                                'token' => $device->fcm_token,
                                'notification' => ['title' => $title, 'body' => $body],
                                'data' => collect($data)
                                    ->mapWithKeys(fn ($value, $key) => [(string) $key => (string) $value])
                                    ->all(),
                                'android' => [
                                    'priority' => 'high',
                                    'notification' => [
                                        'channel_id' => 'umrah_alerts',
                                        'sound' => 'default',
                                    ],
                                ],
                            ],
                        ],
                    );

                if ($response->status() === 404 || str_contains($response->body(), 'UNREGISTERED')) {
                    // Jika token sudah tidak valid menurut Firebase, perangkat
                    // dinonaktifkan agar tidak terus dicoba pada pengiriman berikutnya.
                    $device->update(['fcm_token' => null, 'revoked_at' => now()]);
                } elseif ($response->failed()) {
                    Log::warning('FCM message could not be delivered.', [
                        'device_id' => $device->id,
                        'status' => $response->status(),
                        'response' => $response->json(),
                    ]);
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * @return array<string, string>|null
     */
    private function credentials(): ?array
    {
        $source = config('services.firebase.credentials');
        if (! is_string($source) || trim($source) === '') {
            return null;
        }

        // FIREBASE_CREDENTIALS bisa berupa path file JSON di hosting,
        // atau isi JSON langsung. Di production lebih aman memakai path file.
        $json = str_starts_with(ltrim($source), '{')
            ? $source
            : (is_file($source) ? file_get_contents($source) : false);
        $credentials = is_string($json) ? json_decode($json, true) : null;

        return is_array($credentials)
            && isset($credentials['project_id'], $credentials['client_email'], $credentials['private_key'])
                ? $credentials
                : null;
    }

    /**
     * @param  array<string, string>  $credentials
     */
    private function accessToken(array $credentials): string
    {
        // Access token Google berlaku singkat. Cache 50 menit supaya sistem
        // tidak meminta token baru setiap kali mengirim notifikasi.
        return Cache::remember(
            'firebase-access-token-'.sha1($credentials['client_email']),
            now()->addMinutes(50),
            function () use ($credentials): string {
                $now = time();
                $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
                $claims = $this->base64Url(json_encode([
                    'iss' => $credentials['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud' => 'https://oauth2.googleapis.com/token',
                    'iat' => $now,
                    'exp' => $now + 3600,
                ], JSON_THROW_ON_ERROR));
                $unsignedToken = "{$header}.{$claims}";
                openssl_sign($unsignedToken, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

                return Http::asForm()
                    ->post('https://oauth2.googleapis.com/token', [
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion' => "{$unsignedToken}.{$this->base64Url($signature)}",
                    ])
                    ->throw()
                    ->json('access_token');
            },
        );
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
