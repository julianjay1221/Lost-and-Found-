<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\User;
use App\Notifications\ItemClaimed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClaimMatchedObjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_lost_owner_can_claim_a_specific_matching_found_report(): void
    {
        [$lostOwner, , $lostReport, $foundReport] = $this->matchingReports();

        $this
            ->actingAs($lostOwner)
            ->patch(route('reports.claim', $lostReport), [
                'matched_report_id' => $foundReport->id,
            ])
            ->assertRedirect(route('reports.show', $lostReport));

        $lostReport->refresh();
        $foundReport->refresh();

        $this->assertSame(ItemReport::STATUS_CLAIMED, $lostReport->status);
        $this->assertSame($foundReport->id, $lostReport->matched_report_id);
        $this->assertNotNull($lostReport->claimed_at);
        $this->assertSame(ItemReport::STATUS_CLAIMED, $foundReport->status);
        $this->assertNotNull($foundReport->claimed_at);
    }

    public function test_lost_owner_can_claim_an_auto_matched_found_report_and_notify_finder_and_admin(): void
    {
        [$lostOwner, $finder, $lostReport, $foundReport] = $this->matchingReports();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lostReport->update([
            'status' => ItemReport::STATUS_FOUND,
            'matched_report_id' => $foundReport->id,
        ]);

        Notification::fake();

        $this
            ->actingAs($lostOwner)
            ->patch(route('reports.claim', $lostReport), [
                'matched_report_id' => $foundReport->id,
            ])
            ->assertRedirect(route('reports.show', $lostReport));

        Notification::assertSentTo(
            $finder,
            ItemClaimed::class,
            fn (ItemClaimed $notification, array $channels) => in_array('database', $channels, true)
        );

        Notification::assertSentTo(
            $admin,
            ItemClaimed::class,
            fn (ItemClaimed $notification, array $channels) => in_array('database', $channels, true)
        );

        $this->assertSame(ItemReport::STATUS_CLAIMED, $lostReport->refresh()->status);
        $this->assertSame(ItemReport::STATUS_CLAIMED, $foundReport->refresh()->status);
    }

    public function test_admin_can_confirm_claim_and_show_claimed_found_item_on_public_board(): void
    {
        [, , $lostReport, $foundReport] = $this->matchingReports();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin-confirm@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lostReport->update([
            'status' => ItemReport::STATUS_CLAIMED,
            'claimed_at' => now(),
            'matched_report_id' => $foundReport->id,
        ]);

        $foundReport->update([
            'status' => ItemReport::STATUS_CLAIMED,
            'claimed_at' => now(),
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('admin.reports.confirm-claim', $lostReport))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertNotNull($lostReport->refresh()->claim_confirmed_at);
        $this->assertNotNull($foundReport->refresh()->claim_confirmed_at);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText($foundReport->item_name)
            ->assertSeeText('Claimed');
    }

    public function test_lost_owner_cannot_claim_a_found_report_that_is_not_a_potential_match(): void
    {
        [$lostOwner, , $lostReport] = $this->matchingReports();

        $unmatchedReport = ItemReport::create([
            'user_id' => User::create([
                'name' => 'Different Finder',
                'email' => 'different-finder@example.com',
                'password' => 'password',
                'role' => 'student',
            ])->id,
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Calculator',
            'category' => 'Electronics',
            'location' => 'Science lab',
            'description' => 'Found a calculator in the science lab.',
            'contact_name' => 'Different Finder',
            'contact_email' => 'different-finder@example.com',
            'status' => ItemReport::STATUS_APPROVED,
        ]);

        $this
            ->actingAs($lostOwner)
            ->patch(route('reports.claim', $lostReport), [
                'matched_report_id' => $unmatchedReport->id,
            ])
            ->assertForbidden();

        $this->assertSame(ItemReport::STATUS_APPROVED, $lostReport->refresh()->status);
        $this->assertSame(ItemReport::STATUS_APPROVED, $unmatchedReport->refresh()->status);
    }

    private function matchingReports(): array
    {
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
            'item_name' => 'Wallet',
            'category' => 'Personal Items',
            'location' => 'Library desk',
            'description' => 'Lost a black wallet near the library desk.',
            'contact_name' => 'Lost Owner',
            'contact_email' => 'lost-owner@example.com',
            'status' => ItemReport::STATUS_APPROVED,
        ]);

        $foundReport = ItemReport::create([
            'user_id' => $finder->id,
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Wallet',
            'category' => 'Personal Items',
            'location' => 'Library desk',
            'description' => 'Found a black wallet at the library desk.',
            'contact_name' => 'Finder Student',
            'contact_email' => 'finder@example.com',
            'status' => ItemReport::STATUS_APPROVED,
        ]);

        return [$lostOwner, $finder, $lostReport, $foundReport];
    }
}
