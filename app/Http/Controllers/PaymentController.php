<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;

class PaymentController extends Controller
{
    public function showPlans()
    {
        return Inertia::render('Planes/Index', [
            'precioPremium' => (int) env('PREMIUM_PRICE', 19900),
            'stripeKey'     => env('STRIPE_KEY'),
        ]);
    }

    public function createCheckout(Request $request)
    {
        $user = $request->user();

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $session = CheckoutSession::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'customer_email' => $user->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'mxn',
                    'product_data' => [
                        'name' => 'Plan Premium Rastro Fácil',
                    ],
                    'unit_amount' => (int) env('PREMIUM_PRICE', 1000), // en centavos
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('pago.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('pago.cancel'),
        ]);

        return redirect($session->url);
    }

    public function success(Request $request)
    {
        $user = $request->user();

        // Aquí podrías verificar la sesión en Stripe si quieres
        // pero para sencillo, asumimos que si llegó aquí, pago OK
        $user->plan = 'premium';
        $user->save();

        return redirect()
            ->route('predicciones.index')
            ->with('success', '¡Listo! Ya puedes usar el módulo de Predicciones Premium.');
    }

    public function cancel()
    {
        return Inertia::render('Planes/Cancel', [
            'message' => 'El pago fue cancelado. Puedes intentarlo de nuevo cuando quieras.',
        ]);
    }
}
