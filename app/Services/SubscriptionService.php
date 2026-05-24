<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

/**
 * SubscriptionService
 * 
 * Centraliza toda la lógica de negocio del módulo Premium.
 * El controlador no debe saber nada de Stripe directamente.
 */
class SubscriptionService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Crea una sesión de Checkout en Stripe y retorna la URL de redirección.
     *
     * @throws ApiErrorException
     */
    public function createCheckoutSession(User $user): StripeSession
    {
        // Evitar que un usuario ya Premium vuelva a pagar
        // (la ruta también está protegida por middleware, esto es una capa extra)
        if ($user->isPremium()) {
            throw new \RuntimeException('El usuario ya tiene plan Premium activo.');
        }

        return StripeSession::create([
            'payment_method_types' => ['card'],
            'mode'                 => 'payment',
            'customer_email'       => $user->email,
            'client_reference_id'  => (string) $user->id, // clave para el webhook
            'metadata'             => [
                'user_id' => $user->id,
                'plan'    => 'premium',
            ],
            'line_items' => [[
                'price_data' => [
                    'currency'     => config('services.stripe.currency', 'mxn'),
                    'product_data' => [
                        'name'        => 'Plan Premium – Rastro Claro',
                        'description' => 'Acceso completo al módulo de Predicciones, IA y reportes avanzados.',
                    ],
                    'unit_amount' => (int) config('services.stripe.premium_price', 19900),
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('pago.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('pago.cancel'),
        ]);
    }

    /**
     * Verifica con Stripe que el pago fue completado y activa Premium.
     *
     * Nunca activar Premium solo porque el usuario llegó a la URL de éxito.
     * Siempre verificar el estado real en Stripe.
     *
     * @throws \RuntimeException si el pago no está completo
     * @throws ApiErrorException si falla la llamada a Stripe
     */
    public function activatePremiumAfterPayment(User $user, string $sessionId): void
    {
        $session = StripeSession::retrieve($sessionId);

        if ($session->payment_status !== 'paid') {
            Log::warning('Intento de activación Premium con pago no completado', [
                'user_id'    => $user->id,
                'session_id' => $sessionId,
                'status'     => $session->payment_status,
            ]);

            throw new \RuntimeException('El pago no ha sido confirmado por Stripe.');
        }

        // Evitar activación duplicada si ya está Premium con esta misma sesión
        if ($user->stripe_checkout_session_id === $sessionId && $user->isPremium()) {
            Log::info('Activación Premium duplicada ignorada', [
                'user_id'    => $user->id,
                'session_id' => $sessionId,
            ]);
            return;
        }

        $user->activatePremium($sessionId);

        Log::info('Usuario activado como Premium', [
            'user_id'    => $user->id,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Activa Premium desde un webhook de Stripe (checkout.session.completed).
     * Busca al usuario por client_reference_id o metadata.user_id.
     */
    public function handleWebhookCheckoutCompleted(StripeSession $session): void
    {
        $userId = $session->client_reference_id
            ?? ($session->metadata->user_id ?? null);

        if (!$userId) {
            Log::error('Webhook checkout.session.completed sin user_id', [
                'session_id' => $session->id,
            ]);
            return;
        }

        $user = User::find($userId);

        if (!$user) {
            Log::error('Webhook: usuario no encontrado', [
                'user_id'    => $userId,
                'session_id' => $session->id,
            ]);
            return;
        }

        if ($session->payment_status !== 'paid') {
            return; // No activar si no está pagado
        }

        // Evitar duplicados
        if ($user->stripe_checkout_session_id === $session->id && $user->isPremium()) {
            return;
        }

        $user->activatePremium($session->id);

        Log::info('Premium activado vía webhook', [
            'user_id'    => $user->id,
            'session_id' => $session->id,
        ]);
    }
}
