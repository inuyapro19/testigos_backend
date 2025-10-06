<?php

namespace App\Events;

use App\Models\CaseModel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CaseModel $case
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
            new PrivateChannel('lawyers'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'case' => [
                'id' => $this->case->id,
                'title' => $this->case->title,
                'victim_name' => $this->case->victim->name,
                'category' => $this->case->category,
                'created_at' => $this->case->created_at,
            ],
        ];
    }
}