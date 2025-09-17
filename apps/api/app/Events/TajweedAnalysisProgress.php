<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Events;

use App\Models\Recitation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired during Tajweed analysis progress.
 * Provides real-time updates on processing status.
 */
class TajweedAnalysisProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $recitation;
    public $stage;
    public $progress;
    public $message;

    /**
     * Create a new event instance.
     *
     * @param Recitation $recitation The recitation being analyzed
     * @param string $stage Current processing stage
     * @param int $progress Progress percentage (0-100)
     * @param string $message Human-readable progress message
     */
    public function __construct(Recitation $recitation, string $stage, int $progress, string $message)
    {
        $this->recitation = $recitation;
        $this->stage = $stage;
        $this->progress = $progress;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Private channel for the student who submitted
            new PrivateChannel('user.' . $this->recitation->user_id),
            
            // Private channel for the teacher if assignment-based
            $this->recitation->assignment_id 
                ? new PrivateChannel('teacher.' . $this->recitation->assignment->class->teacher_id)
                : null,
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'recitation_id' => $this->recitation->id,
            'user_id' => $this->recitation->user_id,
            'assignment_id' => $this->recitation->assignment_id,
            'stage' => $this->stage,
            'progress' => $this->progress,
            'message' => $this->message,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'tajweed.analysis.progress';
    }
}