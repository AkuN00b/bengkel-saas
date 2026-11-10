<?php

namespace Tests\Feature;

use App\Enums\WorkshopRole;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopMembership;
use App\Notifications\SetupMechanicPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MechanicManagementTest extends TestCase
{
    use RefreshDatabase;

    private function owner(string $role = 'owner'): WorkshopMembership
    {
        $membership = WorkshopMembership::factory()->create(['role' => $role]);
        $this->actingAs($membership->user)->withSession(['active_workshop_id' => $membership->workshop_id]);

        return $membership;
    }

    private function createMechanic(WorkshopMembership $owner): User
    {
        $this->post('/mechanics', ['workshop_context' => $owner->workshop_id, 'name' => 'Mekanik Baru', 'email' => 'mechanic@example.test',
            'role' => 'owner', 'is_super_admin' => true, 'needs_password_setup' => false, 'password' => 'InjectedPassword123!'])
            ->assertRedirect(route('mechanics.index'));

        return User::where('email', 'mechanic@example.test')->firstOrFail();
    }

    public function test_owner_creates_only_new_pending_mechanic_and_sends_safe_setup_notification(): void
    {
        Notification::fake();
        $owner = $this->owner();
        $user = $this->createMechanic($owner);
        $this->assertTrue($user->needs_password_setup);
        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse(Hash::check('InjectedPassword123!', $user->password));
        $this->assertTrue($user->hasWorkshopRole($owner->workshop, WorkshopRole::Mechanic));
        $this->assertFalse($user->hasWorkshopRole($owner->workshop, WorkshopRole::Owner));
        Notification::assertSentTo($user, SetupMechanicPassword::class, function ($notice) use ($user): bool {
            $mail = $notice->toMail($user);
            $this->assertStringContainsString('Buat sandi', $mail->actionText);
            $this->assertStringStartsWith(config('app.url').'/reset-password/', $mail->actionUrl);
            $this->assertNotSame($notice->token, DB::table('password_reset_tokens')->where('email', $user->email)->value('token'));

            return true;
        });
        $this->get('/mechanics')->assertOk()->assertSee('Menunggu pembuatan sandi')->assertDontSee('InjectedPassword123!');
    }

    public function test_pending_account_cannot_login_until_valid_single_use_setup_then_normal_reset_still_works(): void
    {
        Notification::fake();
        $owner = $this->owner();
        $user = $this->createMechanic($owner);
        $user->forceFill(['password' => 'KnownPassword123!'])->save();
        $notice = Notification::sent($user, SetupMechanicPassword::class)->first();
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'KnownPassword123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $payload = ['email' => $user->email, 'token' => $notice->token, 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'];
        $this->post('/reset-password', $payload)->assertRedirect(route('login'));
        $this->assertFalse($user->fresh()->needs_password_setup);
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'NewPassword123!'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');
        $token = Password::createToken($user);
        $this->post('/reset-password', array_replace($payload, ['token' => $token, 'password' => 'NextPassword123!', 'password_confirmation' => 'NextPassword123!']))->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NextPassword123!', $user->fresh()->password));
    }

    public function test_expired_setup_token_does_not_activate_user_and_resend_is_throttled(): void
    {
        Notification::fake();
        $owner = $this->owner();
        $user = $this->createMechanic($owner);
        $notice = Notification::sent($user, SetupMechanicPassword::class)->first();
        $membership = WorkshopMembership::where('user_id', $user->id)->sole();
        $payload = ['workshop_context' => $owner->workshop_id, 'version' => 1];
        $this->post('/mechanics/'.$membership->id.'/resend', $payload)->assertSessionHas('status', fn ($message) => str_contains($message, 'dibatasi'));
        Notification::assertSentToTimes($user, SetupMechanicPassword::class, 1);
        $this->post('/mechanics/'.$membership->id.'/resend', $payload)->assertStatus(409);
        $this->travel(61)->minutes();
        $this->post('/logout');
        $this->post('/reset-password', ['email' => $user->email, 'token' => $notice->token, 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'])->assertSessionHasErrors('email');
        $this->assertTrue($user->fresh()->needs_password_setup);
        $this->actingAs($owner->user)->withSession(['active_workshop_id' => $owner->workshop_id]);
        $this->post('/mechanics/'.$membership->id.'/resend', array_replace($payload, ['version' => 2]))->assertSessionHas('status', fn ($message) => str_contains($message, 'dikirim'));
        Notification::assertSentToTimes($user, SetupMechanicPassword::class, 2);
    }

    public function test_smtp_failure_keeps_committed_pending_account_and_allows_retry(): void
    {
        $owner = $this->owner();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('private transport details'));
        $user = $this->createMechanic($owner);
        $this->assertTrue($user->needs_password_setup);
        $this->assertDatabaseHas('workshop_user', ['user_id' => $user->id, 'role' => 'mechanic', 'is_active' => true]);
        $this->get('/mechanics')->assertSee('Email gagal dikirim')->assertDontSee('private transport details');
        $this->travel(61)->seconds();
        Notification::fake();
        $membership = WorkshopMembership::where('user_id', $user->id)->sole();
        $this->post('/mechanics/'.$membership->id.'/resend', ['workshop_context' => $owner->workshop_id, 'version' => 1])->assertRedirect();
        Notification::assertSentTo($user, SetupMechanicPassword::class);
    }

    #[DataProvider('loggingMailers')]
    public function test_setup_tokens_are_not_sent_to_log_or_logging_failover_mailers(string $mailer): void
    {
        Notification::fake();
        config(['mail.default' => $mailer]);
        $owner = $this->owner();
        $user = $this->createMechanic($owner);
        $this->assertTrue($user->needs_password_setup);
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->get('/mechanics')->assertSee('Email gagal dikirim');
    }

    public static function loggingMailers(): array
    {
        return [['log'], ['failover']];
    }

    #[DataProvider('deniedRoles')]
    public function test_only_owner_manages_accounts_even_with_platform_admin_flag(string $role, bool $admin): void
    {
        $member = $this->owner($role);
        $member->user->forceFill(['is_super_admin' => $admin])->save();
        $this->get('/mechanics')->assertForbidden();
        $this->post('/mechanics', ['workshop_context' => $member->workshop_id, 'name' => 'Forbidden', 'email' => 'no@example.test'])->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    public static function deniedRoles(): array
    {
        return [['cashier', false], ['mechanic', false], ['mechanic', true]];
    }

    public function test_existing_email_stale_context_foreign_membership_and_owner_membership_cannot_be_modified(): void
    {
        $owner = $this->owner();
        $foreign = WorkshopMembership::factory()->create(['role' => 'mechanic']);
        $this->post('/mechanics', ['workshop_context' => $owner->workshop_id, 'name' => 'Other', 'email' => mb_strtoupper($foreign->user->email)])->assertSessionHasErrors('email');
        $this->post('/mechanics', ['workshop_context' => $foreign->workshop_id, 'name' => 'Other', 'email' => 'new@example.test'])->assertStatus(409);
        foreach ([$foreign->id, $owner->id, '99999999999999999999999999999'] as $id) {
            $this->patch('/mechanics/'.$id.'/activation', ['workshop_context' => $owner->workshop_id, 'version' => 1, 'is_active' => false])->assertNotFound();
        }
        $this->assertDatabaseCount('users', 2);
    }

    public function test_deactivation_is_workshop_local_and_immediately_revokes_existing_session_context(): void
    {
        $owner = $this->owner();
        $mechanic = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'mechanic']);
        $other = Workshop::factory()->create();
        $other->users()->attach($mechanic->user_id, ['role' => 'mechanic']);
        $this->patch('/mechanics/'.$mechanic->id.'/activation', ['workshop_context' => $owner->workshop_id, 'version' => 1, 'is_active' => false])->assertRedirect();
        $this->assertFalse($mechanic->user->isMemberOf($owner->workshop));
        $this->assertTrue($mechanic->user->isMemberOf($other));
        $this->actingAs($mechanic->user)->withSession(['active_workshop_id' => $owner->workshop_id]);
        $this->get('/work-orders')->assertRedirect(route('workshops.select'));
        $this->post('/workshops/active', ['workshop_id' => $owner->workshop_id])->assertNotFound();
        $this->withSession(['active_workshop_id' => $other->id])->get('/work-orders')->assertOk();
        $this->actingAs($owner->user)->withSession(['active_workshop_id' => $owner->workshop_id]);
        $this->patch('/mechanics/'.$mechanic->id.'/activation', ['workshop_context' => $owner->workshop_id, 'version' => 2, 'is_active' => true])->assertRedirect();
        $this->assertTrue($mechanic->user->isMemberOf($owner->workshop));
    }
}
