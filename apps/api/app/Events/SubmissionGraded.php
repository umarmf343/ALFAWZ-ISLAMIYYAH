<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Submission;

/**
 * Event fired when a submission is graded/reviewed.
 * Broadcasts to student channels for real-time notifications.
 */
class SubmissionGraded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $submission;

    /**
     * Create a new event instance.
     */
    public function __construct(Submission $submission)
    {
        $this->submission = $submission;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to student's private channel
        $studentId = $this->submission->student_id;
        
        return [
            new PrivateChannel('student.' . $studentId),
            new Channel('grades'), // General grades channel
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'submission.graded';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'submission' => [
                'id' => $this->submission->id,
                'assignment_title' => $this->submission->assignment->title,
                'score' => $this->submission->score,
                'status' => $this->submission->status,
                'graded_at' => $this->submission->updated_at->toISOString(),
            ],
            'message' => 'Your submission for "' . $this->submission->assignment->title . '" has been graded',
            'type' => 'submission_graded',
        ];
    }
}