<?php

namespace Tests\Feature;

use App\Mail\RegisteredSuccessfully;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Demo User',
            'email' => 'demo@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Registered successfully.')
            ->assertJsonPath('data.user.email', 'demo@gmail.com')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                    'access_token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'demo@gmail.com',
        ]);

        $this->assertTrue(Hash::check('password', User::query()->firstOrFail()->password));

        Mail::assertSent(
            RegisteredSuccessfully::class,
            fn (RegisteredSuccessfully $mail): bool => $mail->hasTo('demo@gmail.com')
                && $mail->user->is(User::query()->firstOrFail()),
        );
    }

    public function test_register_requires_unique_email_and_confirmed_password(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'demo@pastelink.app']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Demo User',
            'email' => 'demo@pastelink.app',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['email', 'password']);

        Mail::assertNothingSent();
    }

    public function test_user_can_login_and_access_current_user_endpoint(): void
    {
        $user = User::factory()->create([
            'email' => 'demo@pastelink.app',
            'password' => 'password',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'demo@pastelink.app',
            'password' => 'password',
        ]);

        $token = $loginResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logged in successfully.')
            ->json('data.access_token');

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'demo@pastelink.app');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'demo@pastelink.app',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'demo@pastelink.app',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
                'code' => 'INVALID_CREDENTIALS',
            ]);
    }

    public function test_google_redirect_returns_provider_redirect(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->getJson('/api/v1/auth/google/redirect')
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_google_callback_creates_user_sends_mail_and_returns_token(): void
    {
        Mail::fake();

        $this->mockGoogleCallbackUser(
            id: 'google-123',
            name: 'Google User',
            email: 'google-user@gmail.com',
            avatar: 'https://lh3.googleusercontent.com/avatar.png',
        );

        $response = $this->getJson('/api/v1/auth/google/callback');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logged in with Google successfully.')
            ->assertJsonPath('data.user.email', 'google-user@gmail.com')
            ->assertJsonPath('data.user.google_avatar_url', 'https://lh3.googleusercontent.com/avatar.png')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'google_avatar_url', 'email_verified_at', 'created_at', 'updated_at'],
                    'access_token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Google User',
            'email' => 'google-user@gmail.com',
            'google_id' => 'google-123',
            'google_avatar_url' => 'https://lh3.googleusercontent.com/avatar.png',
        ]);

        Mail::assertSent(
            RegisteredSuccessfully::class,
            fn (RegisteredSuccessfully $mail): bool => $mail->hasTo('google-user@gmail.com'),
        );
    }

    public function test_google_callback_links_existing_user_by_email_without_resending_registration_mail(): void
    {
        Mail::fake();

        $existingUser = User::factory()->create([
            'name' => 'Existing User',
            'email' => 'existing@gmail.com',
            'google_id' => null,
            'google_avatar_url' => null,
            'email_verified_at' => null,
        ]);

        $this->mockGoogleCallbackUser(
            id: 'google-existing-123',
            name: 'Existing Google User',
            email: 'existing@gmail.com',
            avatar: 'https://lh3.googleusercontent.com/existing.png',
        );

        $response = $this->getJson('/api/v1/auth/google/callback');

        $response->assertOk()
            ->assertJsonPath('data.user.id', $existingUser->id)
            ->assertJsonPath('data.user.email', 'existing@gmail.com');

        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'name' => 'Existing Google User',
            'email' => 'existing@gmail.com',
            'google_id' => 'google-existing-123',
            'google_avatar_url' => 'https://lh3.googleusercontent.com/existing.png',
        ]);

        Mail::assertNothingSent();
    }

    public function test_google_callback_requires_email_from_google(): void
    {
        Mail::fake();

        $this->mockGoogleCallbackUser(
            id: 'google-no-email',
            name: 'No Email User',
            email: null,
            avatar: null,
        );

        $this->getJson('/api/v1/auth/google/callback')
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'Google account did not provide an email address.',
                'code' => 'GOOGLE_EMAIL_REQUIRED',
            ]);

        $this->assertDatabaseCount('users', 0);
        Mail::assertNothingSent();
    }

    public function test_user_can_logout_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_current_user_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    private function mockGoogleCallbackUser(string $id, string $name, ?string $email, ?string $avatar): void
    {
        $googleUser = (new SocialiteUser)->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }
}
