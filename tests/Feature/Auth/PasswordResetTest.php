<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function resetData(User $user, string $token, array $overrides = []): array
    {
        return array_replace([
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-secure-password-123',
            'password_confirmation' => 'new-secure-password-123',
        ], $overrides);
    }

    public function test_reset_link_is_sent_without_disclosing_account_existence(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->get('/forgot-password')->assertOk()->assertSee('Lupa kata sandi');
        $this->from('/forgot-password')->post('/forgot-password', ['email' => strtoupper($user->email)])
            ->assertRedirect('/forgot-password')->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $stored = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
            $this->assertNotSame($stored, $notification->token);
            $this->assertTrue(Hash::check($notification->token, $stored));
            $mail = $notification->toMail($user);
            $this->assertSame('Atur ulang kata sandi', $mail->subject);
            $this->assertStringContainsString('/reset-password/'.$notification->token, $mail->actionUrl);
            $this->get($mail->actionUrl)->assertOk()->assertSee('Atur ulang kata sandi');

            return true;
        });

        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'unknown@example.com'])
            ->assertRedirect('/forgot-password')->assertSessionHas('status', __('passwords.sent'));
        Notification::assertCount(1);
    }

    public function test_broker_throttles_repeated_emails_with_the_same_public_response(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        for ($i = 0; $i < 2; $i++) {
            $this->post('/forgot-password', ['email' => $user->email])
                ->assertSessionHas('status', __('passwords.sent'));
        }
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_reset_links_use_the_configured_app_url_instead_of_the_request_host(): void
    {
        Notification::fake();
        config(['app.url' => 'https://bengkel.example']);
        $user = User::factory()->create();

        $this->post('http://untrusted.example/forgot-password', ['email' => $user->email])->assertRedirect();
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->assertStringStartsWith('https://bengkel.example/reset-password/', $notification->toMail($user)->actionUrl);

            return true;
        });
    }

    public function test_password_requests_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => 'unknown@example.com'])->assertRedirect();
        }
        $this->post('/forgot-password', ['email' => 'unknown@example.com'])->assertTooManyRequests();
        $this->post('/reset-password', [])->assertTooManyRequests();
    }

    public function test_valid_token_resets_password_once_and_rotates_remember_token(): void
    {
        $user = User::factory()->create();
        $rememberToken = $user->remember_token;
        $data = $this->resetData($user, Password::createToken($user));

        $this->post('/reset-password', $data)->assertRedirect(route('login'))
            ->assertSessionHas('status', __('passwords.reset'));

        $this->assertTrue(Hash::check($data['password'], $user->fresh()->password));
        $this->assertNotSame($rememberToken, $user->fresh()->remember_token);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertGuest();
        $this->post('/reset-password', $data)->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => $data['password']])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_invalid_and_expired_tokens_do_not_change_the_password(): void
    {
        $user = User::factory()->create();
        $hash = $user->password;
        $this->post('/reset-password', $this->resetData($user, 'invalid-token'))->assertSessionHasErrors('email');

        $token = Password::createToken($user);
        $this->travel(61)->minutes();
        $this->post('/reset-password', $this->resetData($user, $token))->assertSessionHasErrors('email');
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertGuest();
    }

    public function test_token_cannot_reset_another_users_password(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $hash = $other->password;
        $this->post('/reset-password', $this->resetData($other, Password::createToken($owner)))
            ->assertSessionHasErrors('email');
        $this->assertSame($hash, $other->fresh()->password);
        $this->assertGuest();
    }

    public function test_reset_validates_password_and_never_flashes_passwords_or_token(): void
    {
        $user = User::factory()->create();
        $data = $this->resetData($user, Password::createToken($user), ['password_confirmation' => 'mismatch']);
        $this->post('/reset-password', $data)->assertSessionHasErrors('password');
        foreach (['password', 'password_confirmation', 'token'] as $field) {
            $this->assertNull(session()->getOldInput($field));
        }
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_reset_rejects_short_and_overlong_multibyte_passwords(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        foreach (['short', str_repeat('é', 37), "secure-password\0"] as $password) {
            $this->post('/reset-password', $this->resetData($user, $token, [
                'password' => $password, 'password_confirmation' => $password,
            ]))->assertSessionHasErrors('password');
        }
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_forgot_password_requires_a_valid_email(): void
    {
        Notification::fake();
        $this->post('/forgot-password', ['email' => 'invalid'])->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }
}
