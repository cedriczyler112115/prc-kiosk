<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveQueueBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_queue_board_loads_successfully()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('live-queue-board'));
        $response->assertStatus(200);
        $response->assertViewIs('queue.board');
    }

    public function test_live_queue_board_contains_audio_welcome_logic()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('live-queue-board'));
        $response->assertStatus(200);

        // Check for the welcome function definition
        $response->assertSee('function playWelcomeMessage()', false);

        // Check for the call on initialization (via setTimeout)
        $response->assertSee('setTimeout(playWelcomeMessage', false);

        // Check for suppression logic
        $response->assertSee('let isFirstLoad = true;', false);

        // Check for the audio enable prompt
        $response->assertSee('id="audio-enable-prompt"', false);
        $response->assertSee('function enableAudioContext()', false);
    }
}
