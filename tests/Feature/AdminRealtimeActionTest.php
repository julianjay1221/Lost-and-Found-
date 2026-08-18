<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRealtimeActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approve_action_returns_realtime_status_payload(): void
    {
        $admin = $this->admin();
        $report = $this->report(['status' => ItemReport::STATUS_PENDING]);

        $this
            ->actingAs($admin)
            ->patchJson(route('admin.reports.approve', $report))
            ->assertOk()
            ->assertJsonPath('message', 'Report approved successfully.')
            ->assertJsonPath('target_status', ItemReport::STATUS_APPROVED)
            ->assertJsonPath('report.id', $report->id)
            ->assertJsonPath('report.status', ItemReport::STATUS_APPROVED);

        $this->assertSame(ItemReport::STATUS_APPROVED, $report->refresh()->status);
    }

    public function test_admin_remove_spam_action_deletes_report_from_database_and_returns_realtime_payload(): void
    {
        $admin = $this->admin();
        $report = $this->report(['status' => ItemReport::STATUS_PENDING]);

        $this
            ->actingAs($admin)
            ->patchJson(route('admin.reports.block', $report), [
                'return_status' => ItemReport::STATUS_PENDING,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Spam report deleted successfully.')
            ->assertJsonPath('target_status', ItemReport::STATUS_PENDING)
            ->assertJsonPath('target_url', route('admin.dashboard', ['status' => ItemReport::STATUS_PENDING]))
            ->assertJsonPath('report.deleted', true);

        $this->assertDatabaseMissing('item_reports', [
            'id' => $report->id,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.dashboard', ['status' => ItemReport::STATUS_PENDING]))
            ->assertOk()
            ->assertDontSeeText($report->item_name);
    }

    public function test_admin_reject_action_deletes_report_from_database_and_returns_realtime_payload(): void
    {
        $admin = $this->admin();
        $report = $this->report(['status' => ItemReport::STATUS_APPROVED]);

        $this
            ->actingAs($admin)
            ->patchJson(route('admin.reports.reject', $report), [
                'return_status' => ItemReport::STATUS_APPROVED,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Report rejected and deleted successfully.')
            ->assertJsonPath('target_status', ItemReport::STATUS_APPROVED)
            ->assertJsonPath('target_url', route('admin.dashboard', ['status' => ItemReport::STATUS_APPROVED]))
            ->assertJsonPath('report.deleted', true);

        $this->assertDatabaseMissing('item_reports', [
            'id' => $report->id,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.dashboard', ['status' => ItemReport::STATUS_APPROVED]))
            ->assertOk()
            ->assertDontSeeText($report->item_name);
    }

    public function test_admin_archive_action_returns_realtime_archived_status_payload(): void
    {
        $admin = $this->admin();
        $report = $this->report(['status' => ItemReport::STATUS_CLAIMED]);

        $this
            ->actingAs($admin)
            ->patchJson(route('admin.reports.archive', $report))
            ->assertOk()
            ->assertJsonPath('message', 'Report moved to history successfully.')
            ->assertJsonPath('target_status', 'history')
            ->assertJsonPath('target_url', route('admin.dashboard', ['status' => 'history']))
            ->assertJsonPath('report.id', $report->id)
            ->assertJsonPath('report.status', ItemReport::STATUS_ARCHIVED);

        $this->assertSame(ItemReport::STATUS_ARCHIVED, $report->refresh()->status);
        $this->assertNotNull($report->archived_at);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin User',
            'email' => 'admin-' . uniqid() . '@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }

    private function report(array $overrides = []): ItemReport
    {
        $student = User::create([
            'name' => 'Student User',
            'email' => 'student-' . uniqid() . '@example.com',
            'password' => 'password',
            'role' => 'student',
        ]);

        return ItemReport::create(array_merge([
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Realtime Wallet',
            'category' => 'Personal Items',
            'location' => 'Library desk',
            'description' => 'A report for realtime admin action tests.',
            'contact_name' => 'Student User',
            'contact_phone' => '09171234567',
            'status' => ItemReport::STATUS_PENDING,
        ], $overrides));
    }
}
