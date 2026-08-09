<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\User;
use App\Notifications\FoundItemMatchApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApprovalAndClaimNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.sms.driver' => 'log']);
    }

    public function test_approved_found_match_can_be_claimed_and_notifies_admin_and_finder(): void
    {
        Notification::fake();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lostOwner = User::create([
            'name' => 'Lost Owner',
            'email' => 'lost-owner@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        $finder = User::create([
            'name' => 'Finder Student',
            'email' => 'finder@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        $lostReport = ItemReport::create([
            'user_id' => $lostOwner->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Lost Red Bottle',
            'category' => 'Drinkware',
            'location' => 'Library desk',
            'description' => 'Lost a red bottle near the library desk.',
            'contact_name' => 'Lost Owner',
            'contact_email' => 'lost-owner@example.com',
            'status' => ItemReport::STATUS_APPROVED,
        ]);

        $foundReport = ItemReport::create([
            'user_id' => $finder->id,
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Found Red Bottle',
            'category' => 'Drinkware',
            'location' => 'Library desk',
            'description' => 'Found a red bottle at the library desk.',
            'contact_name' => 'Finder Student',
            'contact_email' => 'finder@example.com',
            'status' => ItemReport::STATUS_PENDING,
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('admin.reports.approve', $foundReport))
            ->assertRedirect();

        $this->assertSame(ItemReport::STATUS_APPROVED, $foundReport->refresh()->status);

        Notification::assertSentOnDemand(
            FoundItemMatchApproved::class,
            fn (FoundItemMatchApproved $notification, array $channels, $notifiable) => in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === 'lost-owner@example.com'
        );

        $this
            ->actingAs($lostOwner)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSeeText('Found Match')
            ->assertSeeText('Found Red Bottle may match your lost Lost Red Bottle')
            ->assertSeeText('Finder: Finder Student')
            ->assertSeeText('Email: finder@example.com');

        $this
            ->actingAs($lostOwner)
            ->get(route('reports.show', $lostReport))
            ->assertOk()
            ->assertSeeText('Potential Matches')
            ->assertSeeText('Found Red Bottle')
            ->assertSeeText('Claim Object');

        $this
            ->actingAs($lostOwner)
            ->patch(route('reports.claim', $lostReport), [
                'matched_report_id' => $foundReport->id,
            ])
            ->assertRedirect(route('reports.show', $lostReport));

        $this
            ->actingAs($finder)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSeeText('Claimed Match')
            ->assertSeeText('Your found Found Red Bottle may have been claimed by the owner of lost Lost Red Bottle');

        $this
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Notifications')
            ->assertSeeText('Claimed Match')
            ->assertSeeText('Lost Owner claimed Lost Red Bottle')
            ->assertSeeText('Matched found report: Found Red Bottle by Finder Student');
    }

    public function test_approving_found_match_uses_phone_notification_hook_when_lost_owner_has_no_email(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('SMS notification logged.', \Mockery::on(function (array $context) {
                return ($context['to'] ?? null) === '09999999999'
                    && str_contains($context['message'] ?? '', 'Found item match: Found Blue Umbrella may match your lost Lost Blue Umbrella')
                    && str_contains($context['message'] ?? '', 'Finder Name: Finder Student')
                    && str_contains($context['message'] ?? '', 'Email: finder-phone@example.com');
            }));

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-phone@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lostOwner = User::create([
            'name' => 'Lost Owner',
            'email' => 'lost-phone-owner@example.com',
            'contact_phone' => '09171234567',
            'password' => 'password',
            'role' => 'student',
        ]);

        $finder = User::create([
            'name' => 'Finder Student',
            'email' => 'finder-phone@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        ItemReport::create([
            'user_id' => $lostOwner->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Lost Blue Umbrella',
            'category' => 'Personal Items',
            'location' => 'Gym lobby',
            'description' => 'Lost a blue umbrella near the gym lobby.',
            'contact_name' => 'Lost Owner',
            'contact_phone' => '09999999999',
            'status' => ItemReport::STATUS_APPROVED,
        ]);

        $foundReport = ItemReport::create([
            'user_id' => $finder->id,
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Found Blue Umbrella',
            'category' => 'Personal Items',
            'location' => 'Gym lobby',
            'description' => 'Found a blue umbrella at the gym lobby.',
            'contact_name' => 'Finder Student',
            'contact_email' => 'finder-phone@example.com',
            'status' => ItemReport::STATUS_PENDING,
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('admin.reports.approve', $foundReport))
            ->assertRedirect();
    }

    public function test_approving_found_match_notifies_lost_report_contact_phone_and_email(): void
    {
        Notification::fake();

        Log::shouldReceive('info')
            ->once()
            ->with('SMS notification logged.', \Mockery::on(function (array $context) {
                return ($context['to'] ?? null) === '09990000000'
                    && str_contains($context['message'] ?? '', 'Found item match: Found Laptop Charger may match your lost Lost Laptop Charger')
                    && str_contains($context['message'] ?? '', 'Finder Name: Finder Student')
                    && str_contains($context['message'] ?? '', 'Phone: 09176660000')
                    && str_contains($context['message'] ?? '', 'Email: finder-contact@example.com');
            }));

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-sms-first@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lostOwner = User::create([
            'name' => 'Lost Owner',
            'email' => 'lost-sms-first@example.com',
            'contact_phone' => '09175550000',
            'password' => 'password',
            'role' => 'student',
        ]);

        $finder = User::create([
            'name' => 'Finder Student',
            'email' => 'finder-sms-first@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        ItemReport::create([
            'user_id' => $lostOwner->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Lost Laptop Charger',
            'category' => 'Electronics',
            'location' => 'Computer lab',
            'description' => 'Lost a laptop charger in the computer lab.',
            'contact_name' => 'Lost Owner',
            'contact_phone' => '09990000000',
            'contact_email' => 'preferred-contact@example.com',
            'status' => ItemReport::STATUS_PENDING,
        ]);

        $foundReport = ItemReport::create([
            'user_id' => $finder->id,
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Found Laptop Charger',
            'category' => 'Electronics',
            'location' => 'Computer lab',
            'description' => 'Found a laptop charger in the computer lab.',
            'contact_name' => 'Finder Student',
            'contact_phone' => '09176660000',
            'contact_email' => 'finder-contact@example.com',
            'status' => ItemReport::STATUS_PENDING,
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('admin.reports.approve', $foundReport))
            ->assertRedirect()
            ->assertSessionHas('status', 'Report approved. 1 matching lost item owner(s) were notified.');

        Notification::assertSentOnDemand(
            FoundItemMatchApproved::class,
            fn (FoundItemMatchApproved $notification, array $channels, $notifiable) => in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === 'preferred-contact@example.com'
        );

        Notification::assertSentTo(
            $lostOwner,
            FoundItemMatchApproved::class,
            fn (FoundItemMatchApproved $notification, array $channels) => in_array('database', $channels, true)
        );
    }

    public function test_approving_found_match_uses_lost_report_email_when_phone_is_missing(): void
    {
        Notification::fake();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-contact@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lostOwner = User::create([
            'name' => 'Lost Owner',
            'email' => 'account-email@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        $finder = User::create([
            'name' => 'Finder Student',
            'email' => 'finder-contact@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        ItemReport::create([
            'user_id' => $lostOwner->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Lost Laptop Charger',
            'category' => 'Electronics',
            'location' => 'Computer lab',
            'description' => 'Lost a laptop charger in the computer lab.',
            'contact_name' => 'Lost Owner',
            'contact_email' => 'preferred-contact@example.com',
            'status' => ItemReport::STATUS_PENDING,
        ]);

        $foundReport = ItemReport::create([
            'user_id' => $finder->id,
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Found Laptop Charger',
            'category' => 'Electronics',
            'location' => 'Computer lab',
            'description' => 'Found a laptop charger in the computer lab.',
            'contact_name' => 'Finder Student',
            'contact_email' => 'finder-contact@example.com',
            'status' => ItemReport::STATUS_PENDING,
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('admin.reports.approve', $foundReport))
            ->assertRedirect()
            ->assertSessionHas('status', 'Report approved. 1 matching lost item owner(s) were notified.');

        Notification::assertSentOnDemand(
            FoundItemMatchApproved::class,
            function (FoundItemMatchApproved $notification, array $channels, $notifiable) {
                $mail = $notification->toMail($notifiable);
                $lines = implode("\n", array_merge($mail->introLines, $mail->outroLines));

                return in_array('mail', $channels, true)
                    && ($notifiable->routes['mail'] ?? null) === 'preferred-contact@example.com'
                    && str_contains($lines, 'Finder name: Finder Student')
                    && str_contains($lines, 'Finder email: finder-contact@example.com');
            }
        );
    }

    public function test_approving_found_match_falls_back_to_account_email_when_report_email_is_missing(): void
    {
        Notification::fake();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-fallback@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lostOwner = User::create([
            'name' => 'Lost Owner',
            'email' => 'account-fallback@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        $finder = User::create([
            'name' => 'Finder Student',
            'email' => 'finder-fallback@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        ItemReport::create([
            'user_id' => $lostOwner->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Lost Notebook',
            'category' => 'School Supplies',
            'location' => 'Library desk',
            'description' => 'Lost a notebook near the library desk.',
            'contact_name' => 'Lost Owner',
            'status' => ItemReport::STATUS_PENDING,
        ]);

        $foundReport = ItemReport::create([
            'user_id' => $finder->id,
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Found Notebook',
            'category' => 'School Supplies',
            'location' => 'Library desk',
            'description' => 'Found a notebook at the library desk.',
            'contact_name' => 'Finder Student',
            'contact_email' => 'finder-fallback@example.com',
            'status' => ItemReport::STATUS_PENDING,
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('admin.reports.approve', $foundReport))
            ->assertRedirect()
            ->assertSessionHas('status', 'Report approved. 1 matching lost item owner(s) were notified.');

        Notification::assertSentOnDemand(
            FoundItemMatchApproved::class,
            fn (FoundItemMatchApproved $notification, array $channels, $notifiable) => in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === 'account-fallback@example.com'
        );
    }
}
