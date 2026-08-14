<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveLostReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_board_and_live_check_show_approved_found_reports_but_exclude_claimed_lost_reports(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $activeReport = ItemReport::create([
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Active Backpack',
            'category' => 'Bags',
            'contact_name' => $student->name,
            'status' => ItemReport::STATUS_APPROVED,
        ]);

        $resolvedReport = ItemReport::create([
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Resolved Backpack',
            'category' => 'Bags',
            'contact_name' => $student->name,
            'status' => ItemReport::STATUS_CLAIMED,
            'claimed_at' => now(),
        ]);

        $foundReport = ItemReport::create([
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Found Backpack',
            'category' => 'Bags',
            'contact_name' => $student->name,
            'status' => ItemReport::STATUS_APPROVED,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeText('Active Backpack')
            ->assertSeeText('Found Backpack')
            ->assertDontSeeText('Resolved Backpack');

        $this->getJson(route('reports.public-status', ['ids' => [$activeReport->id, $resolvedReport->id, $foundReport->id]]))
            ->assertOk()
            ->assertJsonPath('public_ids.0', $activeReport->id)
            ->assertJsonCount(2, 'public_ids');
    }
}
