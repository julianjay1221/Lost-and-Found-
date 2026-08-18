<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminArchiveReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_claimed_reports_do_not_show_status_change_buttons_on_the_admin_dashboard(): void
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
            ->assertSeeText('Claimed')
            ->assertDontSeeText('Reject')
            ->assertDontSeeText('Remove Spam')
            ->assertDontSeeText('Move to History')
            ->assertDontSeeText('Close History');
    }

    public function test_approved_reports_do_not_show_status_change_buttons_on_the_admin_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $approvedReport = ItemReport::create([
            'user_id' => User::create([
                'name' => 'Found Owner',
                'email' => 'found-owner@example.com',
                'password' => 'password',
                'role' => 'student',
            ])->id,
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Approved Backpack',
            'category' => 'Bags',
            'location' => 'Main hallway',
            'description' => 'An approved report that should only show the photo action.',
            'contact_name' => 'Found Owner',
            'contact_email' => 'found-owner@example.com',
            'photo_path' => 'uploads/item-reports/approved-backpack.jpg',
            'status' => ItemReport::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Approved Backpack')
            ->assertSeeText('Approved')
            ->assertSeeText('View Photo')
            ->assertDontSee('data-target-status="approved"', false)
            ->assertDontSee('data-target-status="rejected"', false)
            ->assertDontSee('data-target-status="spam"', false)
            ->assertDontSee('data-target-status="history"', false)
            ->assertDontSeeText('Move to History')
            ->assertDontSeeText('Close History');
    }
}
