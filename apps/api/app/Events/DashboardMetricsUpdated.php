<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when dashboard metrics are updated.
 * Broadcasts to teacher and admin channels for real-time dashboard updates.
 */
class DashboardMetricsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $metrics;
    public $userId;
    public $userType;

    /**
     * Create a new event instance.
     */
    public function __construct(array $metrics, int $userId, string $userType = 'teacher')
    {
        $this->metrics = $metrics;
        $this->userId = $userId;
        $this->userType = $userType;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // Broadcast to user's private channel
        if ($this->userType === 'teacher') {
            $channels[] = new PrivateChannel('teacher.' . $this->userId);
        } elseif ($this->userType === 'admin') {
            $channels[] = new PrivateChannel('admin.' . $this->userId);
        }
        
        // General dashboard channel
        $channels[] = new Channel('dashboard.metrics');
        
        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'dashboard.metrics.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'metrics' => $this->metrics,
            'user_type' => $this->userType,
            'updated_at' => now()->toISOString(),
            'type' => 'dashboard_metrics_updated',
        ];
    }
}