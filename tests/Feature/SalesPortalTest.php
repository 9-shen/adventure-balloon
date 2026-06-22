<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use Tests\TestCase;
use Maatwebsite\Excel\Facades\Excel;

class SalesPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $salesUser;
    protected User $otherUser;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        // Create Sales User
        $this->salesUser = User::create([
            'name' => 'Sales Person',
            'email' => 'sales@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+212600000000',
            'is_active' => true,
        ]);
        $this->salesUser->assignRole('sales');

        // Create non-sales user
        $this->otherUser = User::create([
            'name' => 'Other Person',
            'email' => 'other@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+212600000001',
            'is_active' => true,
        ]);
        $this->otherUser->assignRole('greeter');

        // Create product
        $this->product = Product::create([
            'name' => 'Premium Flight',
            'base_adult_price' => 2000.00,
            'base_child_price' => 1200.00,
            'is_active' => true,
        ]);
    }

    public function test_sales_user_can_access_sales_portal(): void
    {
        $response = $this->actingAs($this->salesUser)->get('/sales');
        $response->assertStatus(200);
    }

    public function test_non_sales_user_cannot_access_sales_portal(): void
    {
        $response = $this->actingAs($this->otherUser)->get('/sales');
        $response->assertStatus(403);
    }

    public function test_sales_profile_renders_and_email_is_disabled(): void
    {
        Livewire::actingAs($this->salesUser)
            ->test(\App\Filament\Sales\Pages\Profile::class)
            ->assertSet('data.name', 'Sales Person')
            ->assertSet('data.email', 'sales@example.com')
            ->assertFormFieldIsDisabled('personal-information::data::section.email');
    }

    public function test_sales_profile_validation(): void
    {
        Livewire::actingAs($this->salesUser)
            ->test(\App\Filament\Sales\Pages\Profile::class)
            ->set('data.phone', '')
            ->call('save')
            ->assertHasErrors(['data.phone']);
    }

    public function test_sales_can_create_booking_with_overridden_prices(): void
    {
        // Boot the sales panel so Filament route resolution works in tests
        $panel = filament()->getPanel('sales');
        filament()->setCurrentPanel($panel);

        Livewire::actingAs($this->salesUser)
            ->test(\App\Filament\Sales\Resources\Bookings\Pages\CreateSalesBooking::class)
            ->set('data.product_id', $this->product->id)
            ->set('data.flight_date', now()->addDays(2)->format('Y-m-d'))
            ->set('data.adult_pax', 2)
            ->set('data.child_pax', 1)
            ->set('data.pickup_location', 'Test Hotel')
            ->set('data.booking_customers', [
                [
                    'full_name' => 'Passenger 1',
                    'type' => 'adult',
                    'is_primary' => true,
                    'phone' => '+212600000010',
                ],
                [
                    'full_name' => 'Passenger 2',
                    'type' => 'adult',
                    'is_primary' => false,
                ],
                [
                    'full_name' => 'Passenger 3',
                    'type' => 'child',
                    'is_primary' => false,
                ],
            ])
            // Step 3 pricing overrides
            ->set('data.base_adult_price', 1800.00) // Override: 2000 -> 1800
            ->set('data.base_child_price', 1100.00) // Override: 1200 -> 1100
            ->set('data.discount_amount', 300.00)
            ->set('data.discount_reason', 'Special Group Promo')
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect();

        // Total calculated = (1800 * 2) + (1100 * 1) - 300 = 3600 + 1100 - 300 = 4400.
        $booking = Booking::latest()->first();
        $this->assertNotNull($booking);
        $this->assertEquals('regular', $booking->type);
        $this->assertNull($booking->partner_id);
        $this->assertEquals($this->salesUser->id, $booking->created_by);
        $this->assertEquals(1800.00, $booking->base_adult_price);
        $this->assertEquals(1100.00, $booking->base_child_price);
        $this->assertEquals(3600.00, $booking->adult_total);
        $this->assertEquals(1100.00, $booking->child_total);
        $this->assertEquals(300.00, $booking->discount_amount);
        $this->assertEquals('Special Group Promo', $booking->discount_reason);
        $this->assertEquals(4400.00, $booking->final_amount);
        $this->assertEquals(4400.00, $booking->balance_due);
        $this->assertStringStartsWith('BLX-', $booking->booking_ref);
    }

    public function test_sales_cannot_create_booking_with_discount_missing_reason(): void
    {
        // Boot the sales panel so Filament route resolution works in tests
        $panel = filament()->getPanel('sales');
        filament()->setCurrentPanel($panel);

        Livewire::actingAs($this->salesUser)
            ->test(\App\Filament\Sales\Resources\Bookings\Pages\CreateSalesBooking::class)
            ->set('data.product_id', $this->product->id)
            ->set('data.flight_date', now()->addDays(2)->format('Y-m-d'))
            ->set('data.adult_pax', 1)
            ->set('data.pickup_location', 'Test Hotel')
            ->set('data.booking_customers', [
                [
                    'full_name' => 'Passenger 1',
                    'type' => 'adult',
                    'is_primary' => true,
                    'phone' => '+212600000010',
                ],
            ])
            ->set('data.discount_amount', 100.00)
            ->set('data.discount_reason', '') // Empty reason
            ->call('create')
            ->assertHasErrors(['data.discount_reason']);
    }

    public function test_sales_user_can_access_bookings_report_page(): void
    {
        $response = $this->actingAs($this->salesUser)->get('/sales/bookings-reports');
        $response->assertStatus(200);
    }

    public function test_non_sales_user_cannot_access_bookings_report_page(): void
    {
        $response = $this->actingAs($this->otherUser)->get('/sales/bookings-reports');
        $response->assertStatus(403);
    }

    public function test_sales_user_can_export_bookings_report(): void
    {
        // Boot the sales panel so Filament route resolution works in tests
        $panel = filament()->getPanel('sales');
        filament()->setCurrentPanel($panel);

        // Create a booking created by the sales user
        Booking::create([
            'booking_ref' => 'BLX-TEST123',
            'type' => 'regular',
            'product_id' => $this->product->id,
            'flight_date' => now()->addDays(2)->format('Y-m-d'),
            'adult_pax' => 2,
            'child_pax' => 0,
            'pickup_location' => 'Test Hotel',
            'base_adult_price' => 2000.00,
            'base_child_price' => 1200.00,
            'adult_total' => 4000.00,
            'child_total' => 0.00,
            'discount_amount' => 0.00,
            'final_amount' => 4000.00,
            'balance_due' => 4000.00,
            'created_by' => $this->salesUser->id,
            'booking_status' => 'confirmed',
        ]);

        Excel::fake();

        Livewire::actingAs($this->salesUser)
            ->test(\App\Filament\Sales\Resources\BookingsReportResource\Pages\ListBookingsReports::class)
            ->callAction('export_csv');

        Excel::assertDownloaded('my_bookings_report.csv');
    }

    public function test_sales_booking_wizard_with_zero_child_pax_saves_successfully(): void
    {
        // Boot the sales panel so Filament route resolution works in tests
        $panel = filament()->getPanel('sales');
        filament()->setCurrentPanel($panel);

        Livewire::actingAs($this->salesUser)
            ->test(\App\Filament\Sales\Resources\Bookings\Pages\CreateSalesBooking::class)
            ->set('data.product_id', $this->product->id)
            ->set('data.flight_date', now()->addDays(2)->format('Y-m-d'))
            ->set('data.adult_pax', 2)
            ->set('data.child_pax', 0)
            ->set('data.pickup_location', 'Test Hotel')
            ->set('data.booking_customers', [
                [
                    'full_name' => 'Passenger 1',
                    'type' => 'adult',
                    'is_primary' => true,
                    'phone' => '+212600000010',
                ],
                [
                    'full_name' => 'Passenger 2',
                    'type' => 'adult',
                    'is_primary' => false,
                ]
            ])
            ->set('data.base_adult_price', 1800.00)
            ->set('data.discount_amount', 0.00)
            ->call('create')
            ->assertHasNoErrors();

        $booking = Booking::latest()->first();
        $this->assertNotNull($booking);
        $this->assertEquals(2, $booking->adult_pax);
        $this->assertEquals(0, $booking->child_pax);
        $this->assertEquals(1800.00, $booking->base_adult_price);
        $this->assertEquals(3600.00, $booking->final_amount);
    }
}
