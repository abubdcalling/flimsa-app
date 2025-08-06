<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Illuminate\Support\Facades\Validator;


class StripePaymentController extends Controller
{
    // public function PaymentIntent(Request $request)
    // {
    //     Stripe::setApiKey(config('services.stripe.secret'));

    //     $amount = $request->amount * 100;  // Stripe expects amount in cents

    //     $session = Session::create([
    //         'payment_method_types' => ['card'],
    //         'line_items' => [[
    //             'price_data' => [
    //                 'currency' => 'usd',
    //                 'product_data' => [
    //                     'name' => 'Product Name',  // or dynamic from $request
    //                 ],
    //                 'unit_amount' => $amount,
    //             ],
    //             'quantity' => 1,
    //         ]],
    //         'mode' => 'payment',
    //         'success_url' => url('/success'),  // your frontend success route
    //         'cancel_url' => url('/cancel'),  // your frontend cancel route
    //     ]);

    //     return response()->json([
    //         'checkout_url' => $session->url,
    //     ]);
    // }

    public function PaymentIntent(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'product_name' => 'required|string|in:withads,withoutads'  // validate allowed values
        ]);

        $amount = $request->amount * 100;  // Stripe uses cents

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $request->product_name,  // dynamic name: "withads" or "withoutads"
                    ],
                    'unit_amount' => $amount,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => config('app.frontend_url') . '/success?plan_type=' . $request->product_name,
            'cancel_url' => config('app.frontend_url') . '/cancel',
        ]);

        return response()->json([
            'checkout_url' => $session->url,
        ]);
    }

    public function index(): JsonResponse
    {
        // Get all subscriptions with user info if needed
        $subscriptions = UserSubscription::all()->map(function ($subscription) {
            return [
                'user_id' => $subscription->user_id,
                'plan_type' => $subscription->plan_type,
                'created_at' => $subscription->created_at->toDateTimeString(),
                'expire_date' => $subscription->created_at->addDays(30)->toDateTimeString(),  // 30 days expiry
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $subscriptions,
        ]);
    }

    public function paymentStatus(Request $request)
    {
        // return 1;
        $user = Auth::user();  // or $request->user()

        $plantype = $user->plan_type;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user_id' => $user->id,
                'plan_type' => $user->plan_type,
                'created_at' => $user->created_at->toDateTimeString(),
                'expire_date' => $user->updated_at->addDays(30)->toDateTimeString(),
            ]
        ]);
    }

    // public function success(Request $request)
    // {
    //     // return 1;
    //     $user = Auth::user();  // or $request->user()

    //     if (!$user) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'User not authenticated.'
    //         ], 401);
    //     }

    //     // Get plan_type from query string (e.g., /success?plan_type=withads)
    //     $planType = $request->query('plan_type');

    //     if (!$planType) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Missing plan_type in URL.'
    //         ], 400);
    //     }

    //     // 1. Update plan_type in users table
    //     $user->plan_type = $planType;
    //     $user->save();

    //     // 2. Update or insert into user_subscriptions table
    //     UserSubscription::updateOrCreate(
    //         ['user_id' => $user->id],
    //         ['plan_type' => $planType]
    //     );

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Payment completed and subscription updated.',
    //         'user' => $user,
    //         'subscription' => [
    //             'plan_type' => $planType,
    //         ]
    //     ]);
    // }

    public function cancel(Request $request)
    {
        return response()->json([
            'status' => 'cancelled',
            'message' => 'Payment was cancelled.'
        ]);
    }

    public function success(Request $request)
    {
        // Validate query param
        $planType = $request->query('plan_type');
        if (!$planType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing plan_type in URL.'
            ], 400);
        }

        // Validate user info from request
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'gender' => 'nullable|in:male,female,other',
        ]);

        // Create new user
        $user = User::create([
            'first_name' => $validated['first_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'gender' => $validated['gender'] ?? null,
            'plan_type' => $planType,
        ]);

        // Save subscription
        UserSubscription::create([
            'user_id' => $user->id,
            'plan_type' => $planType,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment successful. User created and subscription updated.',
            'user' => $user->only(['id', 'first_name', 'email', 'gender', 'plan_type']),
            'subscription' => [
                'plan_type' => $planType,
            ]
        ]);
    }



    public function verifyPaymentAndCreateUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'first_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'gender' => 'nullable|in:male,female,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = Session::retrieve($request->session_id);

            if ($session->payment_status !== 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment not completed.',
                ], 400);
            }

            // Get product name as plan type (you can use metadata if you want)
            $planType = $session->metadata['plan_type'] ?? 'withads';  // optional

            // Create user
            $user = User::create([
                'first_name' => $request->first_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'gender' => $request->gender,
                'plan_type' => $planType,
            ]);

            // Save subscription
            UserSubscription::create([
                'user_id' => $user->id,
                'plan_type' => $planType,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified and user created.',
                'user' => $user->only(['id', 'first_name', 'email', 'gender', 'plan_type']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stripe error or invalid session.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
