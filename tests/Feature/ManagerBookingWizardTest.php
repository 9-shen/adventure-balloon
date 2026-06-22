<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerBookingWizardTest extends TestCase
{
    use RefreshDatabase;

    protected User $managerUser;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        // Create Manager User
        $this->managerUser = User::create([
            'name' => 'Manager Person',
            'email' => 'manager@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+212600000002',
            'is_active' => true,
        ]);
        $this->managerUser->assignRole('manager');

        // Create product
        $this->product = Product::create([
            'name' => 'Classic Flight',
            'base_adult_price' => 1500.00,
            'base_child_price' => 900.00,
            'is_active' => true,
        ]);
    }

    public function test_manager_can_access_booking_wizard(): void
    {
        $response = $this->actingAs($this->managerUser)->get('/manager/bookings/create');
        $response->assertStatus(200);
    }

    public function test_manager_can_create_booking_with_custom_price_overrides(): void
    {
        // Boot the manager panel context so Filament route resolution works in tests
        $panel = filament()->getPanel('manager');
        filament()->setCurrentPanel($panel);

        Livewire::actingAs($this->managerUser)
            ->test(\App\Filament\Manager\Resources\BookingResource\Pages\CreateBooking::class)
            ->set('data.booking_type', 'regular')
            ->set('data.product_id', $this->product->id)
            ->set('data.flight_date', now()->addDays(2)->format('Y-m-d'))
            ->set('data.adult_pax', 2)
            ->set('data.child_pax', 1)
            ->set('data.pickup_location', 'Hotel Sunrise')
            ->set('data.booking_customers', [
                [
                    'full_name' => 'Manager Passenger 1',
                    'type' => 'adult',
                    'is_primary' => true,
                    'phone' => '+212600000020',
                ],
                [
                    'full_name' => 'Manager Passenger 2',
                    'type' => 'adult',
                    'is_primary' => false,
                ],
                [
                    'full_name' => 'Manager Passenger 3',
                    'type' => 'child',
                    'is_primary' => false,
                ],
            ])
            ->set('data.base_adult_price', 1400.00) // Override: 1500 -> 1400
            ->set('data.base_child_price', 800.00)  // Override: 900 -> 800
            ->set('data.discount_amount', 100.00)
            ->set('data.discount_reason', 'Loyal Customer Discount')
            ->call('create')
            ->assertHasNoErrors();

        // Check if values stored correctly in DB
        // Total should be (1400 * 2) + (800 * 1) - 100 = 2800 + 800 - 100 = 3500
        $booking = Booking::latest()->first();
        $this->assertNotNull($booking);
        $this->assertEquals('regular', $booking->type);
        $this->assertEquals(1400.00, $booking->base_adult_price);
        $this->assertEquals(800.00, $booking->base_child_price);
        $this->assertEquals(2800.00, $booking->adult_total);
        $this->assertEquals(800.00, $booking->child_total);
        $this->assertEquals(100.00, $booking->discount_amount);
        $this->assertEquals('Loyal Customer Discount', $booking->discount_reason);
        $this->assertEquals(3500.00, $booking->final_amount);
    }

    public function test_manager_booking_wizard_disables_unit_prices_on_zero_pax(): void
    {
        $panel = filament()->getPanel('manager');
        filament()->setCurrentPanel($panel);

        $testable = Livewire::actingAs($this->managerUser)
            ->test(\App\Filament\Manager\Resources\BookingResource\Pages\CreateBooking::class)
            ->set('data.booking_type', 'regular')
            ->set('data.product_id', $this->product->id)
            ->set('data.flight_date', now()->addDays(2)->format('Y-m-d'))
            ->set('data.adult_pax', 2)
            ->set('data.child_pax', 0)
            ->set('data.pickup_location', 'Hotel Sunrise')
            ->set('data.booking_customers', [])
            ->set('data.booking_customers', [
                [
                    'full_name' => 'Manager Passenger 1',
                    'type' => 'adult',
                    'is_primary' => true,
                    'phone' => '+212600000020',
                ],
                [
                    'full_name' => 'Manager Passenger 2',
                    'type' => 'adult',
                    'is_primary' => false,
                ]
            ])
            ->assertWizardCurrentStep(1)
            ->goToNextWizardStep()
            ->assertWizardCurrentStep(2)
            ->goToNextWizardStep()
            ->assertWizardCurrentStep(3);

        $schema = $testable->instance()->getSchema('form');
        $components = $schema->getFlatComponents(withHidden: true);

        $baseAdultPriceField = $components['data::wizard.pricing-discounts::data::wizard-step.base_adult_price'] ?? null;
        $baseChildPriceField = $components['data::wizard.pricing-discounts::data::wizard-step.base_child_price'] ?? null;

        $this->assertNotNull($baseAdultPriceField, 'Custom Adult Price field not found');
        $this->assertNotNull($baseChildPriceField, 'Custom Child Price field not found');

        $this->assertFalse($baseAdultPriceField->isDisabled(), 'Adult price field should be enabled');
        $this->assertTrue($baseChildPriceField->isDisabled(), 'Child price field should be disabled');
    }
}
