<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    /**
     * Muestra la página de planes.
     * Pasa el estado premium actual para que el frontend lo use.
     */
    public function showPlans(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Planes/Index', [
            'precioPremium' => (int) config('services.stripe.premium_price', 19900),
            'stripeKey'     => config('services.stripe.key'),
            'esPremium'     => $user->isPremium(),
            'planActual'    => $user->planLabel(),
        ]);
    }

    /**
     * Crea sesión Checkout y redirige a Stripe.
     * Protegida por middleware 'plan.no_premium' para evitar pagos duplicados.
     */
    public function createCheckout(Request $request)
    {
        $user = $request->user();

        // Guard extra: si ya es Premium, redirigir
        if ($user->isPremium()) {
            return redirect()->route('planes.index')
                ->with('info', 'Ya tienes el plan Premium activo.');
        }

        try {
            $session = $this->subscriptionService->createCheckoutSession($user);
            return redirect($session->url);
        } catch (ApiErrorException $e) {
            Log::error('Error al crear sesión Stripe', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return redirect()->route('planes.index')
                ->with('error', 'No se pudo iniciar el proceso de pago. Intenta más tarde.');
        } catch (\RuntimeException $e) {
            return redirect()->route('planes.index')
                ->with('info', $e->getMessage());
        }
    }

    /**
     * Página de éxito: SIEMPRE verificar el pago con Stripe antes de activar.
     * Nunca confiar en que el usuario llegó aquí para activar Premium.
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        $user      = $request->user();

        if (!$sessionId) {
            return redirect()->route('planes.index')
                ->with('error', 'Sesión de pago inválida.');
        }

        try {
            $this->subscriptionService->activatePremiumAfterPayment($user, $sessionId);

            return redirect()->route('predicciones.index')
                ->with('success', '¡Felicidades! Tu plan Premium está activo. Ya puedes usar el módulo de Predicciones.');
        } catch (ApiErrorException $e) {
            Log::error('Error verificando sesión Stripe en success', [
                'user_id'    => $user->id,
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ]);

            return redirect()->route('planes.index')
                ->with('error', 'No pudimos verificar tu pago. Si realizaste el pago, espera unos minutos y recarga.');
        } catch (\RuntimeException $e) {
            // Pago no completado o sesión duplicada benigna
            return redirect()->route('planes.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * El usuario canceló en Stripe.
     */
    public function cancel()
    {
        return redirect()->route('planes.index')
            ->with('info', 'Cancelaste el proceso de pago. Puedes intentarlo cuando quieras.');
    }
}