<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

/**
 * StripeWebhookController
 *
 * Recibe eventos de Stripe y los procesa.
 *
 * IMPORTANTE: Esta ruta DEBE estar en la lista de exclusión de CSRF.
 * Agrega en bootstrap/app.php:
 *   $middleware->validateCsrfTokens(except: ['stripe/webhook']);
 *
 * En Stripe Dashboard > Webhooks, registra:
 *   - checkout.session.completed
 *
 * La verificación de firma es obligatoria para seguridad.
 */
class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function handle(Request $request)
    {
        $secret    = config('services.stripe.webhook_secret');
        $signature = $request->header('Stripe-Signature');

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $event = Webhook::constructEvent(
                $request->getContent(),
                $signature,
                $secret,
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Webhook Stripe: firma inválida', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Webhook Stripe: payload inválido', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        try {
            match ($event->type) {
                'checkout.session.completed' => $this->subscriptionService
                    ->handleWebhookCheckoutCompleted($event->data->object),
                default => Log::debug('Webhook Stripe: evento no manejado', ['type' => $event->type]),
            };
        } catch (\Throwable $e) {
            Log::error('Error procesando webhook Stripe', [
                'event_type' => $event->type,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            // Retornar 500 hace que Stripe reintente el webhook automáticamente
            return response()->json(['error' => 'Internal error'], 500);
        }

        return response()->json(['status' => 'ok']);
    }
}
