<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\MailtrapSender;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly MailtrapSender $mailtrapSender,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        /** @var array{name: string, email: string, password: string} $data */
        $data = $request->validated();

        $user = User::query()->create($data);

        $this->mailtrapSender->sendRegistrationEmail($user);

        return ApiResponse::success(
            data: $this->tokenPayload($user),
            message: 'Registered successfully.',
            status: 201,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var array{email: string, password: string} $credentials */
        $credentials = $request->validated();

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return ApiResponse::error(
                message: 'The provided credentials are incorrect.',
                status: 401,
                code: 'INVALID_CREDENTIALS',
            );
        }

        return ApiResponse::success(
            data: $this->tokenPayload($user),
            message: 'Logged in successfully.',
        );
    }

    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    public function handleGoogleCallback(): JsonResponse
    {
        /** @var SocialiteUser $googleUser */
        $googleUser = Socialite::driver('google')
            ->stateless()
            ->user();

        if (! $googleUser->getEmail()) {
            return ApiResponse::error(
                message: 'Google account did not provide an email address.',
                status: 422,
                code: 'GOOGLE_EMAIL_REQUIRED',
            );
        }

        $user = $this->findOrCreateGoogleUser($googleUser);

        return ApiResponse::success(
            data: $this->tokenPayload($user),
            message: 'Logged in with Google successfully.',
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(message: 'Logged out successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function tokenPayload(User $user): array
    {
        return [
            'user' => $user,
            'access_token' => $user->createToken('api')->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }

    private function findOrCreateGoogleUser(SocialiteUser $googleUser): User
    {
        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            $user->forceFill([
                'name' => $googleUser->getName() ?: $user->name,
                'google_id' => $googleUser->getId(),
                'google_avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return $user;
        }

        $user = User::query()->create([
            'name' => $googleUser->getName() ?: Str::before($googleUser->getEmail(), '@'),
            'email' => $googleUser->getEmail(),
            'password' => Str::random(64),
            'google_id' => $googleUser->getId(),
            'google_avatar_url' => $googleUser->getAvatar(),
            'email_verified_at' => now(),
        ]);

        $this->mailtrapSender->sendRegistrationEmail($user);

        return $user;
    }
}
