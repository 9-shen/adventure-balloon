<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserGuideSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    /**
     * Test user creation with guide role creates associated Guide.
     */
    public function test_user_creation_with_guide_role_creates_associated_guide(): void
    {
        $partner = Partner::create([
            'company_name' => 'Test Partner',
            'email' => 'partner1@example.com',
            'phone' => '+212600000000',
            'status' => 'approved',
            'is_active' => true,
        ]);

        $guideRole = Role::where('name', 'guide')->first();

        // 1. Create the user model record
        $user = User::create([
            'name' => 'John Guide',
            'email' => 'john.guide@example.com',
            'password' => Hash::make('secret123'),
            'phone' => '+212600000000',
            'is_active' => true,
        ]);

        $user->partner_id = $partner->id;
        $user->save();

        // 2. Attach roles (simulating Filament saving relationships)
        $user->roles()->attach($guideRole->id);

        // 3. Instantiate CreateUser page and trigger afterCreate() hook
        $page = new \App\Filament\Admin\Resources\Users\Pages\CreateUser();
        $page->record = $user;

        $reflection = new \ReflectionMethod($page, 'afterCreate');
        $reflection->setAccessible(true);
        $reflection->invoke($page);

        // 4. Assertions
        $this->assertDatabaseHas('guides', [
            'email' => 'john.guide@example.com',
            'partner_id' => $partner->id,
            'name' => 'John Guide',
        ]);

        $user->refresh();
        $guide = Guide::where('email', 'john.guide@example.com')->first();
        $this->assertNotNull($guide);
        $this->assertEquals($guide->id, $user->guide_id);
    }

    /**
     * Test user update with guide role synchronizes details.
     */
    public function test_user_update_with_guide_role_synchronizes_associated_guide(): void
    {
        $partner1 = Partner::create([
            'company_name' => 'Partner One',
            'email' => 'p1@example.com',
            'phone' => '+212600000001',
            'status' => 'approved',
            'is_active' => true,
        ]);

        $partner2 = Partner::create([
            'company_name' => 'Partner Two',
            'email' => 'p2@example.com',
            'phone' => '+212600000002',
            'status' => 'approved',
            'is_active' => true,
        ]);

        $guideRole = Role::where('name', 'guide')->first();

        // 1. Create a user without any role
        $user = User::create([
            'name' => 'Updating Guide',
            'email' => 'updating.guide@example.com',
            'password' => Hash::make('secret123'),
            'phone' => '+212600000003',
            'is_active' => true,
        ]);

        // 2. Update user to guide and assign to Partner 1
        $user->partner_id = $partner1->id;
        $user->save();
        $user->roles()->attach($guideRole->id);

        // 3. Trigger afterSave() hook
        $page = new \App\Filament\Admin\Resources\Users\Pages\EditUser();
        $page->record = $user;

        $reflection = new \ReflectionMethod($page, 'afterSave');
        $reflection->setAccessible(true);
        $reflection->invoke($page);

        // Assert guide was created and linked
        $user->refresh();
        $guide = Guide::where('email', 'updating.guide@example.com')->first();
        $this->assertNotNull($guide);
        $this->assertEquals($guide->id, $user->guide_id);
        $this->assertEquals($partner1->id, $guide->partner_id);

        // 4. Change partner to Partner 2 and change name/phone
        $user->name = 'New Guide Name';
        $user->phone = '+212600000009';
        $user->partner_id = $partner2->id;
        $user->save();

        // Trigger afterSave() hook again
        $reflection->invoke($page);

        // Assert guide was updated
        $guide->refresh();
        $this->assertEquals('New Guide Name', $guide->name);
        $this->assertEquals('+212600000009', $guide->phone);
        $this->assertEquals($partner2->id, $guide->partner_id);

        // 5. Remove the guide role
        $user->roles()->detach($guideRole->id);

        // Trigger afterSave() hook again
        $reflection->invoke($page);

        // Assert user's guide_id is nullified
        $user->refresh();
        $this->assertNull($user->guide_id);
    }

    /**
     * Test Guide creation via model created observer creates User correctly.
     */
    public function test_guide_creation_creates_user_with_correct_partner_and_password(): void
    {
        $partner = Partner::create([
            'company_name' => 'Test Partner 2',
            'email' => 'partner2@example.com',
            'phone' => '+212611111111',
            'status' => 'approved',
            'is_active' => true,
        ]);

        $guide = Guide::create([
            'partner_id' => $partner->id,
            'name' => 'Alice Guide',
            'email' => 'alice.guide@example.com',
            'phone' => '+212611111111',
            'guide_reference' => 'GD-999',
            'is_active' => true,
        ]);

        $user = User::where('email', 'alice.guide@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals($guide->id, $user->guide_id);
        $this->assertEquals($partner->id, $user->partner_id);
        $this->assertTrue($user->hasRole('guide'));
        
        // Verify default password works
        $this->assertTrue(Hash::check('1234567890', $user->password));
    }
}
