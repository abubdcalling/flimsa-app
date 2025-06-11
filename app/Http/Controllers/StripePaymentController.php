<?php

namespace App\Http\Controllers;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;



class StripePaymentController extends Controller
{
    public function PaymentIntent(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $amount = $request->amount * 100; // Stripe expects amount in cents

        $paymentIntent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'usd',
            'payment_method_types' => ['card'],
        ]);

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    }
}

