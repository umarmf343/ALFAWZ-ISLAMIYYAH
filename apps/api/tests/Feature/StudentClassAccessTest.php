<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StudentClassAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['student', 'teacher', 'admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    public function test_student_classes_endpoint_returns_enrolled_classes(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $class = ClassModel::create([
            'teacher_id' => $teacher->id,
            'title' => 'Morning Recitation',
            'description' => 'Daily recitation practice',
            'level' => 1,
        ]);

        $otherClass = ClassModel::create([
            'teacher_id' => $teacher->id,
            'title' => 'Advanced Tajweed',
            'description' => 'For advanced students only',
            'level' => 3,
        ]);

        $class->addMember($student, 'student');

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/classes');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.classes');
        $response->assertJsonPath('data.classes.0.id', $class->id);
        $response->assertJsonPath('data.classes.0.teacher.name', $teacher->name);
        $response->assertJsonPath('data.classes.0.role_in_class', 'student');

        $response->assertJsonMissing(['id' => $otherClass->id]);
    }

    public function test_non_student_cannot_access_student_classes(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        Sanctum::actingAs($teacher);

        $response = $this->getJson('/api/student/classes');

        $response->assertForbidden();
        $response->assertJsonPath('success', false);
    }
}
