<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function registration(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Pemilik Bengkel',
            'workshop_name' => 'Bengkel Uji',
            'email' => 'owner@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ], $overrides);
    }

    public function test_registration_provisions_an_owner_and_authenticates_with_a_new_session(): void
    {
        $this->get('/register')->assertOk()->assertSee('Buat akun');
        $sessionId = session()->getId();
        $this->withCookie(config('session.cookie'), $sessionId);

        $this->post('/register', $this->registration([
            'email' => ' OWNER@EXAMPLE.COM ',
            'role' => 'super_admin',
            'is_super_admin' => true,
            'email_verified_at' => now(),
            'workshop_id' => 999,
        ]))->assertRedirect(route('dashboard'));

        $user = User::sole();
        $this->assertSame('owner@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->getAttribute('role'));
        $this->assertNull($user->getAttribute('workshop_id'));
        $this->assertFalse($user->isSuperAdmin());
        $this->assertDatabaseCount('workshop_user', 1);
        $this->assertTrue(Hash::check('secure-password-123', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $this->get('/dashboard')->assertOk()->assertSee('Pemilik Bengkel');
    }

    public function test_duplicate_email_is_rejected_after_normalization(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);
        $this->post('/register', $this->registration(['email' => 'OWNER@EXAMPLE.COM']))
            ->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    #[DataProvider('invalidRegistrationData')]
    public function test_invalid_registration_is_rejected(array $data, string $field): void
    {
        $this->post('/register', $this->registration($data))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public static function invalidRegistrationData(): array
    {
        return [
            'missing name' => [['name' => ''], 'name'],
            'missing workshop' => [['workshop_name' => ''], 'workshop_name'],
            'array workshop' => [['workshop_name' => ['invalid']], 'workshop_name'],
            'long workshop' => [['workshop_name' => str_repeat('x', 256)], 'workshop_name'],
            'invalid email' => [['email' => 'invalid'], 'email'],
            'array email' => [['email' => ['owner@example.com']], 'email'],
            'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
            'mismatched confirmation' => [['password_confirmation' => 'different-password'], 'password'],
            'bcrypt byte limit' => [['password' => str_repeat('é', 37), 'password_confirmation' => str_repeat('é', 37)], 'password'],
            'null byte' => [['password' => "secure-password\0", 'password_confirmation' => "secure-password\0"], 'password'],
        ];
    }

    public function test_registration_is_rate_limited_even_for_invalid_submissions(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/register', [])->assertUnprocessable();
        }
        $this->postJson('/register', $this->registration())->assertTooManyRequests();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_array_input_can_be_rendered_safely_after_validation(): void
    {
        $this->from('/register')->post('/register', $this->registration([
            'name' => ['invalid'], 'email' => ['invalid'],
        ]))->assertRedirect('/register')->assertSessionHasErrors(['name', 'email']);
        $this->withCookie(config('session.cookie'), session()->getId())
            ->get('/register')->assertOk()->assertSee('Email harus berupa teks.');
    }
}
