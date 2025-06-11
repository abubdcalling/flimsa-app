<?php

namespace App\Http\Controllers;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Stripe\Checkout\Session;


class StripePaymentController extends Controller
{
    public function PaymentIntent(Request $request)
{
    Stripe::setApiKey(config('services.stripe.secret'));

    $amount = $request->amount * 100; // Stripe expects amount in cents

    $session = Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Product Name', // or dynamic from $request
                ],
                'unit_amount' => $amount,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => url('/success'), // your frontend success route
        'cancel_url' => url('/cancel'),   // your frontend cancel route
    ]);

    return response()->json([
        'checkout_url' => $session->url,
    ]);
}
}

