<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session as LaravelSession;
use Illuminate\Support\Facades\Validator;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;

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

        // Store user metadata in Laravel session (server-side)
        // LaravelSession::put('payment_user_data', [
        //     'first_name' => $request->first_name,
        //     'email' => $request->email,
        //     'password' => $request->password,
        //     // 'password_confirmation' => $request->password_confirmation,
        //     'gender' => $request->gender,
        // ]);

        session([
            'pending_user' => [
                'first_name' => $request->first_name,
                'email' => $request->email,
                'password' => bcrypt($request->password), // hash early
                'gender' => $request->gender,
            ]
        ]);

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

    // public function checkout(Request $request)
    // {
    //     Stripe::setApiKey(env('STRIPE_SECRET'));

    //     $session = Session::create([
    //         'payment_method_types' => ['card'],
    //         'line_items' => [[
    //             'price_data' => [
    //                 'currency' => 'usd',
    //                 'product_data' => [
    //                     'name' => $request->plan_type,
    //                 ],
    //                 'unit_amount' => $request->amount * 100,  // convert dollars to cents
    //             ],
    //             'quantity' => 1,
    //         ]],
    //         'mode' => 'payment',
    //         'success_url' => config('app.frontend_url') . $request->success_url . '&session_id={CHECKOUT_SESSION_ID}',
    //         'cancel_url' => $request->cancel_url,
    //         'metadata' => [
    //             'plan_type' => $request->plan_type,
    //             'first_name' => $request->first_name,
    //             'email' => $request->email,
    //             'password' => $request->password,
    //             'password_confirmation' => $request->password_confirmation,
    //             'gender' => $request->gender,
    //         ]
    //     ]);

    //     return response()->json([
    //         'id' => $session->id,
    //         'url' => $session->url,
    //         'plan_type' => $request->plan_type,
    //         'first_name' => $request->first_name,
    //         'email' => $request->email,
    //         'password' => $request->password,
    //         'password_confirmation' => $request->password_confirmation,
    //         'gender' => $request->gender,
    //     ]);
    // }

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

    public function success(Request $request)
    {
        // $userData = LaravelSession::get('payment_user_data');
        // if (!$userData) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'User data not found in session.',
        //     ], 400);
        // }

        // return $userData;
        // Create user

        // return 1;
        $pendingUser = session('pending_user');
        $user = Auth::user();  // or $request->user()

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated.'
            ], 401);
        }

        // // Get plan_type from query string (e.g., /success?plan_type=withads)
        $planType = $request->query('plan_type');

        // $userData = User::create([
        //     'first_name' => $userData['first_name'],
        //     'email' => $userData['email'],
        //     'password' => Hash::make($userData['password']),
        //     'gender' => $userData['gender'],
        //     'plan_type' => $planType,
        // ]);

        if (!$planType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing plan_type in URL.'
            ], 400);
        }

        // 1. Update plan_type in users table
        $user->plan_type = $planType;
        $user->save();

        // 2. Update or insert into user_subscriptions table
        UserSubscription::updateOrCreate(
            ['user_id' => $user->id],
            ['plan_type' => $planType]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Payment completed and subscription updated.',
            'user' => $user,
            'pendingUser' => $pendingUser,
            'subscription' => [
                'plan_type' => $planType,
            ]
        ]);
    }

    public function cancel(Request $request)
    {
        return response()->json([
            'status' => 'cancelled',
            'message' => 'Payment was cancelled.'
        ]);
    }

    // public function success(Request $request)
    // {
    //     // Validate query param
    //     $planType = $request->query('plan_type');
    //     if (!$planType) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Missing plan_type in URL.'
    //         ], 400);
    //     }

    //     // Validate user info from request
    //     $validated = $request->validate([
    //         'first_name' => 'required|string|max:255',
    //         'email' => 'required|email|unique:users,email',
    //         'password' => 'required|string|min:6|confirmed',
    //         'gender' => 'nullable|in:male,female,other',
    //     ]);

    //     // Create new user
    //     $user = User::create([
    //         'first_name' => $validated['first_name'],
    //         'email' => $validated['email'],
    //         'password' => Hash::make($validated['password']),
    //         'gender' => $validated['gender'] ?? null,
    //         'plan_type' => $planType,
    //     ]);

    //     // Save subscription
    //     UserSubscription::create([
    //         'user_id' => $user->id,
    //         'plan_type' => $planType,
    //     ]);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Payment successful. User created and subscription updated.',
    //         'user' => $user->only(['id', 'first_name', 'email', 'gender', 'plan_type']),
    //         'subscription' => [
    //             'plan_type' => $planType,
    //         ]
    //     ]);
    // }

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
            // return $request->session_id;
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

    // ----------------new approach

    // public function PaymentIntent(Request $request)
    // {
    //     // Validate incoming data
    //     $validated = $request->validate([
    //         'amount' => 'required|numeric|min:1',
    //         'plan_type' => 'required|string|in:withads,withoutads',
    //         'first_name' => 'required|string|max:255',
    //         'email' => 'required|email',
    //         'password' => 'required|string|min:6',
    //         'gender' => 'nullable|in:male,female,other',
    //     ]);

    //     Stripe::setApiKey(config('services.stripe.secret'));

    //     $amount = $validated['amount'] * 100;  // Convert to cents

    //     // Create Stripe Checkout Session
    //     $session = Session::create([
    //         'payment_method_types' => ['card'],
    //         'line_items' => [[
    //             'price_data' => [
    //                 'currency' => 'usd',
    //                 'product_data' => [
    //                     'name' => $validated['plan_type'],
    //                 ],
    //                 'unit_amount' => $amount,
    //             ],
    //             'quantity' => 1,
    //         ]],
    //         'mode' => 'payment',
    //         'success_url' => config('app.frontend_url') . '/success?session_id={CHECKOUT_SESSION_ID}',
    //         'cancel_url' => config('app.frontend_url') . '/cancel',
    //         'metadata' => [
    //             'plan_type' => $validated['plan_type'],
    //             'first_name' => $validated['first_name'],
    //             'email' => $validated['email'],
    //             'password' => $validated['password'],  // ⚠️ Only for demo, avoid this in production
    //             'gender' => $validated['gender'] ?? '',
    //         ],
    //     ]);

    //     return response()->json([
    //         'checkout_url' => $session->url,
    //         'session_id' => $session->id,
    //     ]);
    // }

    // public function success(Request $request)
    // {
    //     $sessionId = $request->query('session_id');

    //     if (!$sessionId) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Missing session_id in URL.'
    //         ], 400);
    //     }

    //     Stripe::setApiKey(config('services.stripe.secret'));

    //     try {
    //         // Retrieve session from Stripe
    //         $session = StripeSession::retrieve($sessionId);
    //         $metadata = $session->metadata;

    //         // Check if user already exists
    //         if (User::where('email', $metadata->email)->exists()) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'User with this email already exists.'
    //             ], 409);
    //         }

    //         // Create the user
    //         $user = User::create([
    //             'first_name' => $metadata->first_name,
    //             'email' => $metadata->email,
    //             'password' => Hash::make($metadata->password),
    //             'gender' => $metadata->gender ?? null,
    //             'plan_type' => $metadata->plan_type,
    //         ]);

    //         // Save subscription
    //         UserSubscription::create([
    //             'user_id' => $user->id,
    //             'plan_type' => $metadata->plan_type,
    //         ]);

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Payment successful. User created.',
    //             'user' => $user->only(['id', 'first_name', 'email', 'gender', 'plan_type']),
    //             'subscription' => [
    //                 'plan_type' => $metadata->plan_type,
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to complete payment or create user: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
}
