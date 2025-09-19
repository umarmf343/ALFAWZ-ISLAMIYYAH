<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReaderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'student', 'guard_name' => 'web']);
    }

    public function test_reader_state_defaults_and_updates(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        Sanctum::actingAs($user);

        $initial = $this->getJson('/api/reader/state');
        $initial->assertOk();
        $initial->assertJsonFragment([
            'current_surah' => 1,
            'current_ayah' => 1,
            'font_size' => 'medium',
        ]);

        $update = $this->postJson('/api/reader/state', [
            'current_surah' => 36,
            'current_ayah' => 5,
            'font_size' => 'large',
            'translation_enabled' => true,
            'audio_enabled' => true,
            'reciter_id' => 7,
        ]);

        $update->assertOk();
        $update->assertJsonFragment(['font_size' => 'large']);

        $this->assertDatabaseHas('reader_states', [
            'user_id' => $user->id,
            'current_surah' => 36,
            'current_ayah' => 5,
            'font_size' => 'large',
            'translation_enabled' => true,
            'audio_enabled' => true,
            'reciter_id' => 7,
        ]);

        $refetched = $this->getJson('/api/reader/state');
        $refetched->assertOk();
        $refetched->assertJsonFragment(['current_surah' => 36, 'current_ayah' => 5]);
    }

    public function test_reader_reciters_returns_remote_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        Sanctum::actingAs($user);

        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'Maher Al Mueaqly'],
                    ['id' => 2, 'name' => 'Saad Al Ghamdi'],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/reader/reciters?language=en');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Maher Al Mueaqly']);
    }
}
