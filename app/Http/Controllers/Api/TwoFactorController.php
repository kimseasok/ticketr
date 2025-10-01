<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmTwoFactorRequest;
use App\Http\Requests\UpdateIpRestrictionRequest;
use App\Models\User;
use App\Support\Security\SecurityEventLogger;
use App\Support\Security\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TotpService $totp,
        private readonly SecurityEventLogger $events,
    ) {
    }

    public function enroll(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('manage', $user);

        $secret = $this->totp->generateSecret();
        $recoveryCodes = collect(range(1, 8))->map(fn () => strtoupper(Str::random(10)))->all();

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => array_map(fn ($code) => hash('sha256', $code), $recoveryCodes),
            'two_factor_confirmed_at' => null,
        ])->save();

        $uri = $this->totp->generateUri($secret, $user->email, config('app.name', 'Ticketr'));

        $this->events->log($user, 'two_factor.enrolled', [
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'secret' => $secret,
            'otpauth_uri' => $uri,
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('manage', $user);

        if (! $user->two_factor_secret) {
            return response()->json(['message' => 'Two-factor not initiated'], 422);
        }

        $secret = Crypt::decryptString($user->two_factor_secret);

        if (! $this->totp->verify($secret, $request->validated('code'))) {
            return response()->json(['message' => 'Invalid verification code'], 422);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->events->log($user, 'two_factor.confirmed', [
            'ip' => $request->ip(),
        ]);

        return response()->json(['message' => 'Two-factor authentication confirmed']);
    }

    public function disable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('manage', $user);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->events->log($user, 'two_factor.disabled', [
            'ip' => $request->ip(),
        ]);

        return response()->json(['message' => 'Two-factor authentication disabled']);
    }

    public function updateIpRestrictions(UpdateIpRestrictionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('manage', $user);

        $user->forceFill($request->validated())->save();

        $this->events->log($user, 'security.ip_restrictions.updated', [
            'ip' => $request->ip(),
            'allow_count' => count($request->validated('ip_allowlist', [])),
            'block_count' => count($request->validated('ip_blocklist', [])),
        ]);

        return response()->json(['message' => 'IP restrictions updated']);
    }
}
