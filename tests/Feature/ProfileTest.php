<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/account/profile');

        $response->assertOk();
        $response->assertViewIs('account.profile');
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();
        $originalEmail = $user->email;
        $transaction = Transaction::create([
            'name' => 'Test Transaction',
            'code' => 'TEST',
            'workflow_order' => 1,
            'is_active' => true,
            'transfer_allowed' => true,
            'priority_enabled' => true,
        ]);

        $response = $this->actingAs($user)->patch('/account/profile', [
            'name' => 'Doe, John Mark',
            'email' => 'changed@example.com', // Should be ignored
            'transaction_id' => $transaction->id,
            'counter_id' => '5',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/account/profile');

        $user->refresh();

        $this->assertSame('Doe, John Mark', $user->name);
        $this->assertSame($originalEmail, $user->email); // Ensure email didn't change
        $this->assertSame($transaction->id, $user->transaction_id);
        $this->assertSame('5', $user->counter_id);
    }

    public function test_profile_update_validation()
    {
        $user = User::factory()->create();

        // Invalid name format
        $response = $this->actingAs($user)->patch('/account/profile', [
            'name' => 'John Doe', // Incorrect format
        ]);
        $response->assertSessionHasErrors('name');
    }

    public function test_password_can_be_updated()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->put('/account/password', [
            'current_password' => 'password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(); // Back

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_password_update_validation()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        // Wrong current password
        $response = $this->actingAs($user)->put('/account/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        $response->assertSessionHasErrors('current_password', null, 'updatePassword');

        // Weak password
        $response = $this->actingAs($user)->put('/account/password', [
            'current_password' => 'password',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);
        $response->assertSessionHasErrors('password', null, 'updatePassword');
    }
}
