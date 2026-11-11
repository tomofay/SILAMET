<?php

namespace App\Jobs;

use App\Models\MaintenanceTicket;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMaintenanceTicketTelegramJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 3;

    public function __construct(
        public int $ticketId,
        public string $event = 'created'
    ) {}

    public function handle(TelegramService $telegram): void
    {
        if (! $telegram->isEnabled()) {
            return;
        }

        if ($this->event === 'resolved' && ! config('services.telegram.notify_ticket_resolved', false)) {
            return;
        }

        $ticket = MaintenanceTicket::query()
            ->with(['item', 'reportedBy'])
            ->find($this->ticketId);

        if (! $ticket) {
            return;
        }

        $channel = $this->event === 'resolved' ? 'ticket_resolved' : 'ticket_created';
        $message = $telegram->buildMaintenanceTicketMessage($ticket, $this->event);

        $telegram->sendToChannel($channel, $message);
    }
}

