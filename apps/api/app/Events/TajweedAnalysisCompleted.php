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
 * Event fired when Tajweed analysis is completed.
 * Broadcasts real-time updates to connected clients.
 */
class TajweedAnalysisCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $recitation;
    public $analysisResults;
    public $jobStatus;

    /**
     * Create a new event instance.
     *
     * @param Recitation $recitation The analyzed recitation
     * @param array $analysisResults Tajweed analysis results
     * @param string $jobStatus Job completion status
     */
    public function __construct(Recitation $recitation, array $analysisResults, string $jobStatus = 'completed')
    {
        $this->recitation = $recitation;
        $this->analysisResults = $analysisResults;
        $this->jobStatus = $jobStatus;
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
                
            // Class channel for real-time leaderboard updates
            $this->recitation->assignment_id 
                ? new PrivateChannel('class.' . $this->recitation->assignment->class_id)
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
            'class_id' => $this->recitation->assignment?->class_id,
            'status' => $this->jobStatus,
            'analysis' => [
                'overall_score' => $this->analysisResults['overall_score'] ?? null,
                'pronunciation_score' => $this->analysisResults['pronunciation_score'] ?? null,
                'tajweed_score' => $this->analysisResults['tajweed_score'] ?? null,
                'fluency_score' => $this->analysisResults['fluency_score'] ?? null,
                'pace_score' => $this->analysisResults['pace_score'] ?? null,
                'total_errors' => count($this->analysisResults['tajweed_errors'] ?? []),
                'critical_errors' => count(array_filter(
                    $this->analysisResults['tajweed_errors'] ?? [],
                    fn($error) => $error['severity'] === 'critical'
                )),
            ],
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
        return 'tajweed.analysis.completed';
    }
}