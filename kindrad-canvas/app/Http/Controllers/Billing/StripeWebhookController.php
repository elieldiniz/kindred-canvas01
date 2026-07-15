<?php

namespace App\Http\Controllers\Billing;

use App\Billing\StripeWebhookDispatcher;
use App\Http\Controllers\Controller;
use App\Models\StripeEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly StripeWebhookDispatcher $dispatcher) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload) || empty($payload['id']) || empty($payload['type'])) {
            return response()->json(['error' => 'invalid_payload'], 400);
        }

        $eventId = (string) $payload['id'];

        $event = StripeEvent::firstOrCreate(
            ['event_id' => $eventId],
            [
                'type' => (string) $payload['type'],
                'payload' => $payload,
                'processed_at' => null,
            ],
        );

        if ($event->wasRecentlyCreated === false && $event->processed_at !== null) {
            return response()->json(['status' => 'already_processed'], 200);
        }

        Context::add('stripe_event_id', $eventId);

        try {
            $this->dispatcher->dispatch($payload);

            $event->processed_at = now();
            $event->save();
        } catch (\Throwable $e) {
            Log::error('stripe_webhook_handler_failed', [
                'event_id' => $eventId,
                'type' => $payload['type'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'handler_failed'], 500);
        }

        return response()->json(['status' => 'ok'], 200);
    }
}
