<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionCascadeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    public function test_guide_soft_delete_cascades_and_suffixes_unique_fields(): void
    {
        $partner = Partner::create([
            'company_name' => 'Partner ABC',
            'email' => 'partnerabc@example.com',
            'phone' => '+212600000000',
            'status' => 'approved',
            'is_active' => true,
        ]);

        // 1. Create a Guide (will automatically trigger User profile creation)
        $guide = Guide::create([
            'partner_id' => $partner->id,
            'name' => 'Siham Test',
            'email' => 'siham@adventure-balloon.com',
            'phone' => '+212695679266',
            'guide_reference' => 'Siham',
            'is_active' => true,
        ]);

        $user = User::where('guide_id', $guide->id)->first();
        $this->assertNotNull($user);
        $this->assertEquals('siham@adventure-balloon.com', $user->email);

        // 2. Soft-delete the Guide
        $guide->delete();

        $guide->refresh();
        $user->refresh();

        // 3. Verify unique fields are suffixed and both are soft-deleted
        $this->assertTrue($guide->trashed());
        $this->assertTrue($user->trashed());

        $this->assertStringContainsString('siham@adventure-balloon.com_deleted_', $guide->email);
        $this->assertStringContainsString('Siham_deleted_', $guide->guide_reference);
        $this->assertStringContainsString('siham@adventure-balloon.com_deleted_', $user->email);

        // 4. Create a new Guide with the exact same email and guide_reference
        $newGuide = Guide::create([
            'partner_id' => $partner->id,
            'name' => 'Siham New',
            'email' => 'siham@adventure-balloon.com',
            'phone' => '+212695679266',
            'guide_reference' => 'Siham',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('guides', [
            'id' => $newGuide->id,
            'email' => 'siham@adventure-balloon.com',
            'guide_reference' => 'Siham',
        ]);

        $newUser = User::where('guide_id', $newGuide->id)->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('siham@adventure-balloon.com', $newUser->email);

        // 5. Attempting to restore the original guide should fail due to email/reference conflict
        $this->expectException(\Exception::class);
        $guide->restore();
    }
}
