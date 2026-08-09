<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\User;
use App\Notifications\FoundItemMatchApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealtimeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_fetch_unread_match_notifications_in_realtime_endpoint(): void
    {
        $student = User::create([
            'name' => 'Lost Owner',
            'email' => 'lost-owner@example.com',
            'contact_phone' => '09171234567',
            'password' => 'password',
            'role' => 'student',
        ]);

        $finder = User::create([
            'name' => 'Finder Student',
            'email' => 'finder@example.com',
            'contact_phone' => '09176660000',
            'password' => 'password',
            'role' => 'student',
        ]);

        $lostReport = ItemReport::create([
            'user_id' => $student->id,
            'type' => ItemReport::TYPE_LOST,
            'item_name' => 'Lost Red Bottle',
            'category' => 'Drinkware',
            'location' => 'Library desk',
            'description' => 'Lost a red bottle near the library desk.',
            'contact_name' => 'Lost Owner',
            'contact_phone' => '09171234567',
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
            'contact_phone' => '09176660000',
            'contact_email' => 'finder@example.com',
            'status' => ItemReport::STATUS_APPROVED,
        ]);

        $student->notify(new FoundItemMatchApproved($lostReport, $foundReport));

        $this->assertSame(1, $student->unreadNotifications()->count());

        $this
            ->actingAs($student)
            ->getJson(route('student.notifications.unread'))
            ->assertOk()
            ->assertJsonPath('notifications.0.title', 'Found item match approved')
            ->assertJsonPath('notifications.0.message', 'Found Red Bottle may match your lost Lost Red Bottle. Finder: Finder Student, Phone: 09176660000, Email: finder@example.com.');

        $this->assertSame(0, $student->unreadNotifications()->count());
    }
}
