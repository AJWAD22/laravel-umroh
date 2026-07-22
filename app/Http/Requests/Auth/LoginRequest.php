<?php

namespace App\Http\Requests\Auth;

use App\Models\PilgrimPortalAccount;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identity' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identity = trim($this->string('identity')->toString());
        $user = str_contains($identity, '@')
            ? $this->userFromEmail($identity)
            : $this->userFromPhone($identity);

        if (! $user
            || ! $user->is_active
            || ! Hash::check($this->string('password')->toString(), $user->password)
            || (! $user->canAccessAdminPanel() && ! $user->portalAccount()->exists())) {
            $this->throwFailedAuthenticationException();
        }

        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'identity' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('identity')).'|'.$this->ip());
    }

    /**
     * @throws ValidationException
     */
    private function throwFailedAuthenticationException(): never
    {
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'identity' => 'Email/nomor WhatsApp atau password tidak sesuai.',
        ]);
    }

    private function userFromEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first()
            ?? PilgrimPortalAccount::query()->with('user')->where('email', $email)->first()?->user;
    }

    private function userFromPhone(string $phone): ?User
    {
        $phone = preg_replace('/\D+/', '', $phone) ?: '';
        $phone = str_starts_with($phone, '0') ? '62'.substr($phone, 1) : $phone;

        return PilgrimPortalAccount::query()->with('user')->where('phone', $phone)->first()?->user;
    }
}
