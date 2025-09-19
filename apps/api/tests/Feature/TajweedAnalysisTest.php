<?php

namespace Tests\Feature;

use App\Jobs\ProcessTajweedAnalysis;
use App\Models\Recitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TajweedAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'student', 'guard_name' => 'web']);
    }

    public function test_student_can_submit_tajweed_analysis(): void
    {
        Storage::fake('s3');
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('student');

        Sanctum::actingAs($user);

        $audio = UploadedFile::fake()->create('recitation.mp3', 120, 'audio/mpeg');

        $response = $this->postJson('/api/tajweed/analyze', [
            'audio' => $audio,
            'surah_id' => 1,
            'ayah_from' => 1,
            'ayah_to' => 3,
            'expected_tokens' => [
                'token-a', 'token-b', 'token-c',
                'token-d', 'token-e', 'token-f',
                'token-g', 'token-h', 'token-i',
            ],
            'duration_seconds' => 45,
        ]);

        $response->assertCreated();

        $recitation = Recitation::first();

        $this->assertNotNull($recitation);
        $this->assertSame(1, $recitation->surah);
        $this->assertEquals([
            'token-a', 'token-b', 'token-c',
            'token-d', 'token-e', 'token-f',
            'token-g', 'token-h', 'token-i',
        ], $recitation->expected_tokens);
        $this->assertSame(45, $recitation->duration_seconds);

        $this->assertDatabaseHas('whisper_jobs', [
            'recitation_id' => $recitation->id,
            'status' => 'queued',
        ]);

        Queue::assertPushed(ProcessTajweedAnalysis::class);
    }
}
