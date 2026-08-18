<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_report_form_has_a_category_dropdown_for_students(): void
    {
        $student = $this->student('category-form@example.com');

        $this
            ->actingAs($student)
            ->get(route('reports.create', ['type' => ItemReport::TYPE_LOST]))
            ->assertOk()
            ->assertSee('id="category"', false)
            ->assertSee('id="category_custom"', false)
            ->assertSeeText('Add new category...');
    }

    public function test_report_form_prefills_contact_phone_from_the_user_profile(): void
    {
        $student = $this->student('prefill-phone@example.com', [
            'contact_phone' => '09170000001',
        ]);

        $this
            ->actingAs($student)
            ->get(route('reports.create', ['type' => ItemReport::TYPE_FOUND]))
            ->assertOk()
            ->assertSee('id="contact_phone"', false)
            ->assertSee('value="09170000001"', false);
    }

    public function test_student_can_submit_lost_report_with_a_new_category(): void
    {
        $student = $this->student('lost-category@example.com');

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $this->reportPayload([
                'type' => ItemReport::TYPE_LOST,
                'category' => '__custom_category__',
                'category_custom' => 'Musical Instruments',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('item_reports', [
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_LOST,
            'category' => 'Musical Instruments',
            'status' => ItemReport::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('item_categories', [
            'name' => 'Musical Instruments',
            'name_key' => 'musical instruments',
        ]);
    }

    public function test_student_can_submit_found_report_with_a_new_category(): void
    {
        $student = $this->student('found-category@example.com');

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $this->reportPayload([
                'type' => ItemReport::TYPE_FOUND,
                'category' => '__custom_category__',
                'category_custom' => 'Lab Equipment',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('item_reports', [
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_FOUND,
            'category' => 'Lab Equipment',
            'status' => ItemReport::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('item_categories', [
            'name' => 'Lab Equipment',
            'name_key' => 'lab equipment',
        ]);
    }

    public function test_new_category_appears_in_category_selection_after_it_is_saved(): void
    {
        $student = $this->student('selection-category@example.com');

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $this->reportPayload([
                'category' => '__custom_category__',
                'category_custom' => 'Dorm Supplies',
            ]))
            ->assertRedirect();

        $this
            ->actingAs($student)
            ->get(route('reports.create'))
            ->assertOk()
            ->assertSeeText('Dorm Supplies');
    }

    public function test_public_board_has_a_category_dropdown_with_default_categories(): void
    {
        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee('name="category"', false)
            ->assertSeeText('Electronics')
            ->assertSeeText('Wallets & Money');
    }

    public function test_public_board_category_dropdown_auto_submits_without_filter_button(): void
    {
        $this
            ->get(route('home'))
            ->assertOk()
            ->assertDontSeeText('Filter')
            ->assertSee('id="public-board-filter"', false)
            ->assertSee('name="category" data-auto-submit', false);
    }

    public function test_public_board_report_action_keeps_admin_out_of_student_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'public-admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Admin Dashboard')
            ->assertDontSee('href="' . route('student.dashboard') . '"', false);
    }

    public function test_public_board_selection_shows_matching_report_image(): void
    {
        $student = $this->student('public-found@example.com');

        ItemReport::create($this->approvedReportPayload($student, [
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Found Camera',
            'category' => 'Electronics',
            'photo_path' => 'uploads/item-reports/found-camera.jpg',
        ]));

        $this
            ->get(route('home', ['type' => ItemReport::TYPE_FOUND]))
            ->assertOk()
            ->assertSeeText('Found Camera')
            ->assertSee('uploads/item-reports/found-camera.jpg', false);
    }

    public function test_public_board_empty_type_selection_shows_no_report_details(): void
    {
        $student = $this->student('public-empty-type@example.com');

        ItemReport::create($this->approvedReportPayload($student, [
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Only Found Bag',
            'category' => 'Bags',
            'photo_path' => 'uploads/item-reports/only-found-bag.jpg',
        ]));

        $this
            ->get(route('home', ['type' => ItemReport::TYPE_LOST]))
            ->assertOk()
            ->assertDontSeeText('Only Found Bag')
            ->assertDontSee('uploads/item-reports/only-found-bag.jpg', false)
            ->assertDontSeeText('No approved reports found');
    }

    public function test_found_report_form_hides_location_field_and_allows_submission_without_location(): void
    {
        $student = $this->student('found-no-location@example.com');
        $payload = $this->reportPayload([
            'type' => ItemReport::TYPE_FOUND,
        ]);
        unset($payload['location']);

        $this
            ->actingAs($student)
            ->get(route('reports.create', ['type' => ItemReport::TYPE_FOUND]))
            ->assertOk()
            ->assertDontSeeText('Pick Up Location');

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('item_reports', [
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_FOUND,
            'location' => null,
        ]);
    }

    public function test_lost_report_form_allows_submission_without_location(): void
    {
        $student = $this->student('lost-no-location@example.com');
        $payload = $this->reportPayload([
            'type' => ItemReport::TYPE_LOST,
        ]);
        unset($payload['location']);

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('item_reports', [
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_LOST,
            'location' => null,
        ]);
    }

    public function test_lost_report_rejects_a_future_happened_at_date_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 10:00:00', config('app.timezone')));

        $student = $this->student('lost-future-date@example.com');

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $this->reportPayload([
                'type' => ItemReport::TYPE_LOST,
                'happened_at' => '2026-08-17T11:00',
            ]))
            ->assertSessionHasErrors([
                'happened_at' => 'Invalid date/time',
            ]);

        $this->assertDatabaseMissing('item_reports', [
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_LOST,
        ]);
    }

    public function test_lost_report_accepts_current_happened_at_date_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 10:00:00', config('app.timezone')));

        $student = $this->student('lost-current-date@example.com');

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $this->reportPayload([
                'type' => ItemReport::TYPE_LOST,
                'happened_at' => '2026-08-17T10:00',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('item_reports', [
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_LOST,
        ]);
    }

    public function test_found_report_rejects_a_future_happened_at_date_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 10:00:00', config('app.timezone')));

        $student = $this->student('found-future-date@example.com');

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $this->reportPayload([
                'type' => ItemReport::TYPE_FOUND,
                'happened_at' => '2026-08-17T11:00',
            ]))
            ->assertSessionHasErrors([
                'happened_at' => 'Invalid date/time',
            ]);

        $this->assertDatabaseMissing('item_reports', [
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_FOUND,
        ]);
    }

    public function test_found_report_accepts_current_happened_at_date_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 10:00:00', config('app.timezone')));

        $student = $this->student('found-current-date@example.com');

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $this->reportPayload([
                'type' => ItemReport::TYPE_FOUND,
                'happened_at' => '2026-08-17T10:00',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('item_reports', [
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_FOUND,
        ]);
    }

    public function test_lost_report_rejects_a_future_happened_at_date_time_with_seconds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 13:48:30', config('app.timezone')));

        $student = $this->student('lost-future-seconds@example.com');

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $this->reportPayload([
                'type' => ItemReport::TYPE_LOST,
                'happened_at' => '2026-08-18T13:48:31',
            ]))
            ->assertSessionHasErrors([
                'happened_at' => 'Invalid date/time',
            ]);
    }

    public function test_found_report_accepts_current_happened_at_date_time_with_seconds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 13:48:30', config('app.timezone')));

        $student = $this->student('found-current-seconds@example.com');

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $this->reportPayload([
                'type' => ItemReport::TYPE_FOUND,
                'happened_at' => '2026-08-18T13:48:30',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('item_reports', [
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_FOUND,
        ]);
    }

    public function test_student_can_submit_report_without_description(): void
    {
        $student = $this->student('optional-description@example.com');
        $payload = $this->reportPayload();
        unset($payload['description']);

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('item_reports', [
            'user_id' => $student->id,
            'item_name' => 'Sample Item',
            'description' => null,
        ]);
    }

    public function test_student_can_submit_lost_report_without_an_item_name(): void
    {
        $student = $this->student('optional-lost-item-name@example.com');
        $payload = $this->reportPayload(['item_name' => '   ']);

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('item_reports', [
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => null,
        ]);
    }

    public function test_student_can_submit_found_report_without_an_item_name(): void
    {
        $student = $this->student('optional-found-item-name@example.com');
        $payload = $this->reportPayload([
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => null,
        ]);

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('item_reports', [
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => null,
        ]);
    }

    public function test_student_reports_use_the_updated_profile_contact_phone(): void
    {
        $student = $this->student('updated-phone@example.com', [
            'contact_phone' => '09170000001',
        ]);

        $this
            ->actingAs($student)
            ->patch(route('student.profile.update'), [
                'email' => $student->email,
                'contact_phone' => '09170000002',
            ])
            ->assertRedirect();

        $this
            ->actingAs($student)
            ->get(route('reports.create', ['type' => ItemReport::TYPE_LOST]))
            ->assertOk()
            ->assertSee('value="09170000002"', false);

        $payload = $this->reportPayload([
            'contact_phone' => null,
        ]);
        unset($payload['contact_phone']);

        $this
            ->actingAs($student)
            ->post(route('reports.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('item_reports', [
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_LOST,
            'contact_phone' => '09170000002',
        ]);
    }

    private function student(string $email, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Student User',
            'email' => $email,
            'password' => 'password',
            'role' => 'student',
        ], $overrides));
    }

    private function reportPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Sample Item',
            'category' => 'Personal Items',
            'happened_at' => now()->format('Y-m-d\TH:i'),
            'location' => 'Library desk',
            'description' => 'A detailed enough description of the item.',
            'contact_name' => 'Student User',
            'contact_phone' => '09171234567',
            'contact_email' => 'student@example.com',
        ], $overrides);
    }

    private function approvedReportPayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $user->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Public Item',
            'category' => 'Personal Items',
            'happened_at' => now(),
            'location' => 'Library desk',
            'description' => 'A public approved report description.',
            'contact_name' => $user->name,
            'contact_phone' => '09171234567',
            'contact_email' => $user->email,
            'status' => ItemReport::STATUS_APPROVED,
        ], $overrides);
    }
}
