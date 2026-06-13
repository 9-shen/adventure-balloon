<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Partner;
use App\Models\Guide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortalProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Partner $partner;
    protected Guide $guide;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        // Create a partner company
        $this->partner = Partner::create([
            'company_name' => 'Nouaman LLC',
            'email' => 'nouaman@yopmail.com',
            'phone' => '+212600000000',
            'status' => 'approved',
            'is_active' => true,
        ]);

        // Create a Guide record
        $this->guide = Guide::create([
            'partner_id' => $this->partner->id,
            'name' => 'Test Guide User',
            'email' => 'guide@example.com',
            'phone' => '+212611111111',
            'guide_reference' => 'GD-001',
            'is_active' => true,
        ]);

        // Retrieve guide user created by Guide observer
        $this->user = User::where('email', 'guide@example.com')->first();
        // Reset password to a known state
        $this->user->password = Hash::make('password123');
        $this->user->save();
    }

    /**
     * Test that guide profile page displays correctly and has read-only email.
     */
     public function test_guide_profile_renders_and_email_is_disabled(): void
     {
         Livewire::actingAs($this->user)
             ->test(\App\Filament\Guide\Pages\Profile::class)
             ->assertSet('data.name', 'Test Guide User')
             ->assertSet('data.email', 'guide@example.com')
             ->assertSet('data.phone', '+212611111111')
             ->assertFormFieldIsDisabled('personal-information::data::section.email');
     }
 
     /**
      * Test that changing name and phone works successfully.
      */
     public function test_guide_profile_allows_updating_name_and_phone(): void
     {
         Livewire::actingAs($this->user)
             ->test(\App\Filament\Guide\Pages\Profile::class)
             ->set('data.name', 'Updated Guide Name')
             ->set('data.phone', '+212699999999')
             ->call('save')
             ->assertHasNoErrors();
 
         $this->user->refresh();
         $this->assertEquals('Updated Guide Name', $this->user->name);
         $this->assertEquals('+212699999999', $this->user->phone);
 
         $this->guide->refresh();
         $this->assertEquals('Updated Guide Name', $this->guide->name);
         $this->assertEquals('+212699999999', $this->guide->phone);
     }
 
     /**
      * Test that changing password fails without entering correct current password.
      */
     public function test_guide_profile_fails_password_reset_without_current_password(): void
     {
         Livewire::actingAs($this->user)
             ->test(\App\Filament\Guide\Pages\Profile::class)
             ->set('data.new_password', 'newsecret123')
             ->set('data.new_password_confirmation', 'newsecret123')
             ->call('save')
             ->assertHasErrors(['data.current_password']);
 
         // Check password was not changed
         $this->user->refresh();
         $this->assertTrue(Hash::check('password123', $this->user->password));
     }
 
     /**
      * Test that changing password fails if current password is wrong.
      */
     public function test_guide_profile_fails_password_reset_with_incorrect_current_password(): void
     {
         Livewire::actingAs($this->user)
             ->test(\App\Filament\Guide\Pages\Profile::class)
             ->set('data.current_password', 'wrongpassword')
             ->set('data.new_password', 'newsecret123')
             ->set('data.new_password_confirmation', 'newsecret123')
             ->call('save')
             ->assertHasErrors(['data.current_password']);
 
         $this->user->refresh();
         $this->assertTrue(Hash::check('password123', $this->user->password));
     }
 
     /**
      * Test that changing password succeeds with correct current password.
      */
     public function test_guide_profile_succeeds_password_reset_with_correct_current_password(): void
     {
         Livewire::actingAs($this->user)
             ->test(\App\Filament\Guide\Pages\Profile::class)
             ->set('data.current_password', 'password123')
             ->set('data.new_password', 'newsecret123')
             ->set('data.new_password_confirmation', 'newsecret123')
             ->call('save')
             ->assertHasNoErrors();

        $this->user->refresh();
        $this->assertTrue(Hash::check('newsecret123', $this->user->password));
    }

    /**
     * Test that Partner profile page works for both Company and User details.
     */
    public function test_partner_profile_updates_company_and_user_details(): void
    {
        $partnerRole = Role::where('name', 'partner')->first();
        
        $partnerUser = User::create([
            'name' => 'Partner Owner',
            'email' => 'partner_owner@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+212600000002',
            'partner_id' => $this->partner->id,
            'is_active' => true,
        ]);
        $partnerUser->roles()->attach($partnerRole->id);

        Livewire::actingAs($partnerUser)
            ->test(\App\Filament\Partner\Pages\Profile::class)
            // Edit company details
            ->set('data.company_name', 'Nouaman LLC Brand New')
            // Edit user details
            ->set('data.user_name', 'Partner Owner Updated')
            ->set('data.user_phone', '+212699999991')
            // Reset password
            ->set('data.current_password', 'password123')
            ->set('data.new_password', 'newsecret123')
            ->set('data.new_password_confirmation', 'newsecret123')
            ->call('save')
            ->assertHasNoErrors();

        $this->partner->refresh();
        $this->assertEquals('Nouaman LLC Brand New', $this->partner->company_name);

        $partnerUser->refresh();
        $this->assertEquals('Partner Owner Updated', $partnerUser->name);
        $this->assertEquals('+212699999991', $partnerUser->phone);
        $this->assertTrue(Hash::check('newsecret123', $partnerUser->password));
    }
}
