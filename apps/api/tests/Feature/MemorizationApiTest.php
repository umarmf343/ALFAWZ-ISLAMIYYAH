<?php

namespace Tests\Feature;

use App\Models\SrsQueue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemorizationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'student', 'guard_name' => 'web']);
    }

    public function test_student_can_create_and_list_memorization_plans(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/memorization/plans', [
            'title' => 'Juz Amma Focus',
            'surahs' => [112, 113, 114],
            'daily_target' => 2,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addWeek()->toDateString(),
        ]);

        $response->assertCreated();

        $planId = $response->json('data.plan.id');

        $this->assertDatabaseHas('memorization_plans', [
            'id' => $planId,
            'user_id' => $user->id,
            'title' => 'Juz Amma Focus',
        ]);

        $this->assertGreaterThan(0, SrsQueue::where('plan_id', $planId)->count());

        $listResponse = $this->getJson('/api/memorization/plans');

        $listResponse->assertOk();
        $listResponse->assertJsonFragment(['title' => 'Juz Amma Focus']);
        $listResponse->assertJsonStructure([
            'success',
            'data' => [
                ['id', 'title', 'stats' => ['total_items', 'due_today', 'average_confidence']],
            ],
        ]);
    }

    public function test_review_updates_srs_queue_and_progress(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        Sanctum::actingAs($user);

        $planResponse = $this->postJson('/api/memorization/plans', [
            'title' => 'Surah Al-Falaq',
            'surahs' => [113],
            'daily_target' => 1,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(3)->toDateString(),
        ]);

        $planResponse->assertCreated();

        $planId = $planResponse->json('data.plan.id');

        $reviewResponse = $this->postJson('/api/memorization/review', [
            'plan_id' => $planId,
            'surah_id' => 113,
            'ayah_number' => 1,
            'confidence_score' => 0.9,
        ]);

        $reviewResponse->assertOk();

        $srsEntry = SrsQueue::where('plan_id', $planId)
            ->where('user_id', $user->id)
            ->where('surah_id', 113)
            ->where('ayah_id', 1)
            ->first();

        $this->assertNotNull($srsEntry);
        $this->assertSame(0.9, $srsEntry->confidence_score);
        $this->assertTrue($srsEntry->due_at->greaterThanOrEqualTo(Carbon::today()->addDay()));
        $this->assertGreaterThanOrEqual(1, $srsEntry->review_count);

        $this->assertDatabaseHas('quran_progress', [
            'user_id' => $user->id,
            'surah_id' => 113,
            'ayah_number' => 1,
            'memorized_confidence' => 0.9,
        ]);
    }
}
