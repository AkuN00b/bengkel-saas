<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_protected_routes(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->post('/logout')->assertRedirect(route('login'));
        $this->getJson('/dashboard')->assertUnauthorized();
    }

    public function test_login_rotates_session_and_follows_the_protected_destination(): void
    {
        $user = User::factory()->create();
        $this->get('/dashboard')->assertRedirect(route('login'));
        $sessionId = session()->getId();
        $this->withCookie(config('session.cookie'), $sessionId);

        $this->post('/login', ['email' => strtoupper($user->email), 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get('/dashboard')->assertOk()->assertSee($user->name);
    }

    public function test_invalid_password_and_unknown_account_have_the_same_error(): void
    {
        $user = User::factory()->create();
        foreach ([$user->email, 'unknown@example.com'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'incorrect-password'])
                ->assertSessionHasErrors(['email' => __('auth.failed')]);
            $this->assertGuest();
        }
    }

    public function test_failed_login_is_locked_temporarily_and_recovers_after_expiry(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => __('auth.throttle', ['seconds' => 60])]);
        $this->assertGuest();

        $this->travel(61)->seconds();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_clears_failed_attempts_and_can_remember_the_user(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => '1'])
            ->assertRedirect(route('dashboard'))->assertCookie(auth()->getRecallerName());
        $this->assertSame(0, RateLimiter::attempts($user->email.'|127.0.0.1'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_requests_are_rate_limited_even_when_emails_change(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->postJson('/login', ['email' => "unknown{$i}@example.com", 'password' => 'wrong'])
                ->assertUnprocessable();
        }
        $this->postJson('/login', ['email' => 'another@example.com', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_logout_invalidates_session_and_rotates_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['active_workshop_id' => 123])->get('/dashboard')->assertOk();
        $sessionId = session()->getId();
        $csrf = session()->token();
        $this->withCookie(config('session.cookie'), $sessionId);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($csrf, session()->token());
        $this->assertFalse(session()->has('active_workshop_id'));
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/logout')->assertMethodNotAllowed();
    }

    #[DataProvider('guestOnlyPages')]
    public function test_authenticated_users_cannot_access_guest_forms(string $uri): void
    {
        $this->actingAs(User::factory()->create())->get($uri)->assertRedirect(route('dashboard'));
    }

    public static function guestOnlyPages(): array
    {
        return [['/login'], ['/register'], ['/forgot-password'], ['/reset-password/example']];
    }

    public function test_password_changes_invalidate_existing_authenticated_sessions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->withCookie(config('session.cookie'), session()->getId());
        $user->forceFill(['password' => Hash::make('changed-password-123')])->save();
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_user_supplied_html_is_escaped_on_the_account_page(): void
    {
        $this->actingAs(User::factory()->create(['name' => '<script>alert(1)</script>']))
            ->get('/dashboard')->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
