<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinderClaimNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finder_sees_alert_when_matching_lost_report_is_claimed(): void
    {
        $finder = User::create([
            'name' => 'Finder Student',
            'email' => 'finder@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        $owner = User::create([
            'name' => 'Owner Student',
            'email' => 'owner@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        $otherStudent = User::create([
            'name' => 'Other Student',
            'email' => 'other@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        $foundReport = ItemReport::create([
            'user_id' => $finder->id,
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Wallet',
            'category' => 'Personal Items',
            'location' => 'Library desk',
            'description' => 'A black wallet was turned in at the library desk.',
            'contact_name' => 'Finder Student',
            'contact_email' => 'finder@example.com',
            'status' => ItemReport::STATUS_APPROVED,
        ]);

        ItemReport::create([
            'user_id' => $owner->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Wallet',
            'category' => 'Personal Items',
            'location' => 'Library desk',
            'description' => 'A black wallet with school ID inside.',
            'contact_name' => 'Owner Student',
            'contact_email' => 'owner@example.com',
            'status' => ItemReport::STATUS_CLAIMED,
            'claimed_at' => now(),
            'matched_report_id' => $foundReport->id,
        ]);

        $this
            ->actingAs($finder)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSeeText('Claimed Match')
            ->assertSeeText('Your found Wallet may have been claimed by the owner of lost Wallet')
            ->assertSeeText('View My Found Report');

        $this
            ->actingAs($otherStudent)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSeeText('Claimed Match')
            ->assertDontSeeText('may have been claimed');
    }
}
