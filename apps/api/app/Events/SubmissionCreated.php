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
 * Event fired when a new submission is created.
 * Broadcasts to teacher channels for real-time notifications.
 */
class SubmissionCreated implements ShouldBroadcast
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
        // Broadcast to teacher's private channel
        $teacherId = $this->submission->assignment->teacher_id;
        
        return [
            new PrivateChannel('teacher.' . $teacherId),
            new Channel('submissions'), // General submissions channel
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'submission.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'submission' => [
                'id' => $this->submission->id,
                'student_name' => $this->submission->student->name,
                'assignment_title' => $this->submission->assignment->title,
                'created_at' => $this->submission->created_at->toISOString(),
                'status' => $this->submission->status,
            ],
            'message' => 'New submission received from ' . $this->submission->student->name,
            'type' => 'submission_created',
        ];
    }
}