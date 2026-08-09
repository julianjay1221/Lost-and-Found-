<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminArchiveReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_move_claimed_report_to_archive_history(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $claimedReport = ItemReport::create([
            'user_id' => User::create([
                'name' => 'Lost Owner',
                'email' => 'lost-owner@example.com',
                'password' => 'password',
                'role' => 'student',
            ])->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Archive Wallet',
            'category' => 'Personal Items',
            'location' => 'Library desk',
            'description' => 'A resolved wallet report.',
            'contact_name' => 'Lost Owner',
            'contact_email' => 'lost-owner@example.com',
            'status' => ItemReport::STATUS_CLAIMED,
            'claimed_at' => now(),
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Archive Wallet')
            ->assertSeeText('Move to Archive');

        $this
            ->actingAs($admin)
            ->patch(route('admin.reports.archive', $claimedReport))
            ->assertRedirect(route('admin.dashboard'));

        $claimedReport->refresh();

        $this->assertSame(ItemReport::STATUS_ARCHIVED, $claimedReport->status);
        $this->assertNotNull($claimedReport->archived_at);

        $this
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSeeText('Archive Wallet');

        $this
            ->actingAs($admin)
            ->get(route('admin.dashboard', ['status' => ItemReport::STATUS_ARCHIVED]))
            ->assertOk()
            ->assertSeeText('Archive Wallet')
            ->assertSeeText('archived');
    }
}
