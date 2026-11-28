<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_admin_cannot_access_profile_and_profile_link_is_hidden(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/profile')->assertForbidden();
        $this->actingAs($admin)->patch('/profile', ['name' => 'A', 'email' => $admin->email])->assertForbidden();
        $this->actingAs($admin)->delete('/profile', ['password' => 'password'])->assertForbidden();

        $dashboard = $this->actingAs($admin)->get('/admin/dashboard');
        $dashboard->assertOk();
        $dashboard->assertDontSee(route('profile.edit'), false);
        $dashboard->assertDontSee('>Profile<', false);

        $periodePage = $this->actingAs($admin)->get('/admin/periode');
        $periodePage->assertOk();
        $periodePage->assertDontSee(route('profile.edit'), false);
    }

    public function test_lpm_cannot_access_profile_and_profile_link_is_hidden(): void
    {
        $lpm = User::factory()->create(['is_lpm' => true]);

        $this->actingAs($lpm)->get('/profile')->assertForbidden();
        $this->actingAs($lpm)->patch('/profile', ['name' => 'L', 'email' => $lpm->email])->assertForbidden();
        $this->actingAs($lpm)->delete('/profile', ['password' => 'password'])->assertForbidden();

        $dashboard = $this->actingAs($lpm)->get('/lpm/dashboard');
        $dashboard->assertOk();
        $dashboard->assertDontSee(route('profile.edit'), false);
        $dashboard->assertDontSee('>Profile<', false);

        $monitoring = $this->actingAs($lpm)->get('/lpm/monitoring');
        $monitoring->assertOk();
        $monitoring->assertDontSee(route('profile.edit'), false);
    }

    public function test_penyusun_and_reviewer_can_still_access_profile(): void
    {
        $penyusun = User::factory()->create(['is_penyusun' => true]);
        $reviewer = User::factory()->create(['is_reviewer' => true]);

        $penyusunProfile = $this->actingAs($penyusun)->get('/profile');
        $penyusunProfile->assertOk();
        $penyusunProfile->assertSee('Profile Information');
        $penyusunProfile->assertSee('Update Password');

        $reviewerProfile = $this->actingAs($reviewer)->get('/profile');
        $reviewerProfile->assertOk();
        $reviewerProfile->assertSee('Profile Information');
        $reviewerProfile->assertSee('Update Password');
    }
}
