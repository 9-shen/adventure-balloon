<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManualPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    public function test_guest_cannot_access_user_manual_page(): void
    {
        $response = $this->get('/admin/user-manual-page');

        // Should redirect to login
        $response->assertRedirect();
    }

    public function test_admin_roles_can_access_user_manual_page(): void
    {
        // Find or create a super_admin / admin user from the database
        $user = User::role(['super_admin', 'admin'])->first();
        $created = false;

        if (!$user) {
            $user = User::factory()->create([
                'is_active' => true,
            ]);
            $user->assignRole('super_admin');
            $created = true;
        }

        $response = $this->actingAs($user)->get('/admin/user-manual-page');

        $response->assertStatus(200);
        $response->assertSee('Operations & Portal Manual');

        // Clean up if created during test
        if ($created) {
            $user->delete();
        }
    }
}
