<?php

namespace App\Events;

use App\Models\Investment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvestmentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Investment $investment
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->investment->case->victim_id),
            new PrivateChannel('case.' . $this->investment->case_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'investment' => [
                'id' => $this->investment->id,
                'amount' => $this->investment->amount,
                'case_id' => $this->investment->case_id,
                'investor_name' => $this->investment->investor->name,
                'created_at' => $this->investment->created_at,
            ],
        ];
    }
}