<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentReportDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_delete_their_own_unresolved_report(): void
    {
        $student = $this->student('student@example.com');

        $report = $this->reportFor($student, [
            'status' => ItemReport::STATUS_PENDING,
        ]);

        $this
            ->actingAs($student)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSeeText('Delete Report');

        $this
            ->actingAs($student)
            ->delete(route('reports.destroy', $report))
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseMissing('item_reports', [
            'id' => $report->id,
        ]);
    }

    public function test_student_cannot_delete_another_students_report(): void
    {
        $owner = $this->student('owner@example.com');
        $otherStudent = $this->student('other@example.com');
        $report = $this->reportFor($owner);

        $this
            ->actingAs($otherStudent)
            ->delete(route('reports.destroy', $report))
            ->assertForbidden();

        $this->assertDatabaseHas('item_reports', [
            'id' => $report->id,
        ]);
    }

    public function test_student_cannot_delete_claimed_report(): void
    {
        $student = $this->student('claimed-owner@example.com');

        $report = $this->reportFor($student, [
            'status' => ItemReport::STATUS_CLAIMED,
            'claimed_at' => now(),
        ]);

        $this
            ->actingAs($student)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertDontSeeText('Delete Report');

        $this
            ->actingAs($student)
            ->delete(route('reports.destroy', $report))
            ->assertForbidden();
    }

    private function student(string $email): User
    {
        return User::create([
            'name' => 'Student User',
            'email' => $email,
            'password' => 'password',
            'role' => 'student',
        ]);
    }

    private function reportFor(User $student, array $attributes = []): ItemReport
    {
        return ItemReport::create(array_merge([
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Mistake Report',
            'category' => 'Personal Items',
            'location' => 'Library desk',
            'description' => 'Created by mistake.',
            'contact_name' => $student->name,
            'contact_email' => $student->email,
            'status' => ItemReport::STATUS_APPROVED,
        ], $attributes));
    }
}
