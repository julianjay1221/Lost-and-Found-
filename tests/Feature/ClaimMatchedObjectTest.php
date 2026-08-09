<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
