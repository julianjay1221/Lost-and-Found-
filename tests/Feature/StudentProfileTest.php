<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sidebar_hides_removed_links_and_shows_profile_link(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this
            ->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('<i>⌕</i>Browse Found Items', false)
            ->assertDontSee('<i>♧</i>Notifications', false)
            ->assertSeeText('Search Items')
            ->assertSeeText('Profile');
    }

    public function test_student_can_update_profile_details_password_and_photo(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'student-profile@example.com',
            'contact_phone' => null,
        ]);

        $photoPath = tempnam(sys_get_temp_dir(), 'profile-photo') . '.png';
        file_put_contents(
            $photoPath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
        );

        $response = $this
            ->actingAs($student)
            ->patch(route('student.profile.update'), [
                'email' => 'updated-profile@example.com',
                'contact_phone' => '09171234567',
                'password' => 'New@1234',
                'password_confirmation' => 'New@1234',
                'profile_photo' => new UploadedFile($photoPath, 'profile.png', 'image/png', null, true),
            ]);

        $response->assertRedirect();

        $student->refresh();

        $this->assertSame('updated-profile@example.com', $student->email);
        $this->assertSame('09171234567', $student->contact_phone);
        $this->assertTrue(Hash::check('New@1234', $student->password));
        $this->assertNotNull($student->profile_photo_path);
        $this->assertFileExists(public_path($student->profile_photo_path));

        File::delete(public_path($student->profile_photo_path));
        File::delete($photoPath);
    }

    public function test_student_profile_page_shows_password_strength_indicator(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this
            ->actingAs($student)
            ->get(route('student.profile'))
            ->assertOk()
            ->assertSee('data-password-strength-input', false)
            ->assertSeeText('Password strength');
    }

    public function test_admin_cannot_access_student_profile_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($admin)
            ->get(route('student.profile'))
            ->assertForbidden();
    }
}
