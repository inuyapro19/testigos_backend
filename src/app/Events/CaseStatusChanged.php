<?php

namespace App\Events;

use App\Models\CaseModel;
use App\Enums\CaseStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CaseModel $case,
        public CaseStatus $oldStatus,
        public CaseStatus $newStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->case->victim_id),
            new PrivateChannel('case.' . $this->case->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'case_id' => $this->case->id,
            'old_status' => [
                'value' => $this->oldStatus->value,
                'label' => $this->oldStatus->label(),
            ],
            'new_status' => [
                'value' => $this->newStatus->value,
                'label' => $this->newStatus->label(),
            ],
            'updated_at' => $this->case->updated_at,
        ];
    }
}