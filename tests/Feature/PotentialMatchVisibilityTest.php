<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PotentialMatchVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_lost_report_owner_can_see_potential_matches(): void
    {
        [$lostOwner, $finder, $lostReport] = $this->matchingReports();

        $this
            ->actingAs($lostOwner)
            ->get(route('reports.show', $lostReport))
            ->assertOk()
            ->assertSeeText('Potential Matches')
            ->assertSeeText('Blue Umbrella Found')
            ->assertSeeText('Claim Object');
    }

    public function test_found_report_owner_cannot_see_potential_matches(): void
    {
        [, $finder, , $foundReport] = $this->matchingReports();

        $this
            ->actingAs($finder)
            ->get(route('reports.show', $foundReport))
            ->assertOk()
            ->assertDontSeeText('Potential Matches')
            ->assertDontSeeText('Claim Object')
            ->assertDontSeeText('Blue Umbrella Lost');
    }

    public function test_admin_cannot_see_potential_matches_on_user_report(): void
    {
        [, , $lostReport] = $this->matchingReports();

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('reports.show', $lostReport))
            ->assertOk()
            ->assertDontSeeText('Potential Matches')
            ->assertDontSeeText('Claim Object')
            ->assertDontSeeText('Blue Umbrella Found');
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
            'item_name' => 'Blue Umbrella Lost',
            'category' => 'Accessories',
            'location' => 'Library desk',
            'description' => 'Lost a blue umbrella near the library desk.',
            'contact_name' => 'Lost Owner',
            'contact_email' => 'lost-owner@example.com',
            'status' => ItemReport::STATUS_APPROVED,
        ]);

        $foundReport = ItemReport::create([
            'user_id' => $finder->id,
            'type' => ItemReport::TYPE_FOUND,
            'item_name' => 'Blue Umbrella Found',
            'category' => 'Accessories',
            'location' => 'Library desk',
            'description' => 'Found a blue umbrella with a silver handle.',
            'contact_name' => 'Finder Student',
            'contact_email' => 'finder@example.com',
            'status' => ItemReport::STATUS_APPROVED,
        ]);

        return [$lostOwner, $finder, $lostReport, $foundReport];
    }
}
