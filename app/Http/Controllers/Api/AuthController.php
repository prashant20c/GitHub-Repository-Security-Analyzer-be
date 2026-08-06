<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AuthEmailService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        app(AuthEmailService::class)->sendVerification($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email address before signing in.',
                'code' => 'email_not_verified',
                'user' => $user,
                'token' => $user->createToken('auth_token')->plainTextToken,
            ], 403);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function sendVerificationNotification(Request $request, AuthEmailService $emailService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email address is already verified.']);
        }

        $emailService->sendVerification($user);

        return response()->json(['message' => 'Verification email sent.']);
    }

    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403, 'Invalid verification link.');

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Email address verified successfully.']);
        }

        return redirect()->away(rtrim((string) config('services.frontend_url'), '/') . '/email-verified?verified=1');
    }

    public function forgotPassword(Request $request, AuthEmailService $emailService): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink(
            ['email' => $validated['email']],
            static function (User $user, string $token) use ($emailService): void {
                $emailService->sendPasswordReset($user, $token);
            },
        );

        if ($status === Password::RESET_THROTTLED) {
            return response()->json(['message' => 'Please wait before requesting another reset email.'], 429);
        }

        return response()->json(['message' => 'If that email is registered, a password reset link has been sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $validated,
            static function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => 'This password reset link is invalid or has expired.'], 422);
        }

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
