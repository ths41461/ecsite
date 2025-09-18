<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\StripeClient;
use App\Http\Controllers\StripeWebhookController;

class ReplayStripeEvent extends Command
{
    protected $signature = 'stripe:webhook:replay {event_id : The Stripe event ID to replay}';

    protected $description = 'Fetch a Stripe event and process it through the webhook pipeline.';

    public function handle(StripeWebhookController $controller): int
    {
        $eventId = (string) $this->argument('event_id');

        if (!config('stripe.secret')) {
            $this->error('Stripe secret key is not configured.');
            return self::FAILURE;
        }

        $client = new StripeClient(config('stripe.secret'));

        try {
            $event = $client->events->retrieve($eventId, []);
        } catch (\Throwable $e) {
            $this->error('Unable to retrieve Stripe event: ' . $e->getMessage());
            return self::FAILURE;
        }

        try {
            $controller->processStripeEvent($event);
            $this->info("Replayed Stripe event {$eventId} successfully.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to process event: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
