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
    public function PaymentIntent(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'product_name' => 'required|string|in:withads,withoutads'
        ]);

        $amount = (int) ($request->amount * 100);

        $prices = [
            'withads' => 'price_1S0yg2FtHzDQUzoKAMutBmKa',  // replace with your Stripe Price ID
            'withoutads' => 'price_1S0ygPFtHzDQUzoKPJYzyLkF'  // replace with your Stripe Price ID
        ];

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        // 'name' => $request->product_name,  // dynamic name: "withads" or "withoutads"
                        'name' => $prices[$request->product_name],
                        'recurring' => ['interval' => 'month'],
                    ],
                    'unit_amount' => $amount,
                ],
                'quantity' => 1,
            ]],
            // 'mode' => 'payment',
            'mode' => 'subscription',
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

    public function success(Request $request)
    {
        // ✅ Validate input
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'gender' => 'required|in:male,female,other',
            'product_name' => 'required|in:withads,withoutads',
        ]);

        // ✅ Create the user
        $user = User::create([
            'first_name' => $validated['first_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'gender' => $validated['gender'],
            'plan_type' => $validated['product_name'],
        ]);

        // ✅ Create or update subscription
        UserSubscription::updateOrCreate(
            ['user_id' => $user->id],
            ['plan_type' => $validated['product_name']]
        );

        // ✅ Respond with success
        return response()->json([
            'status' => 'success',
            'message' => 'User created and subscription activated.',
            'user' => $user,
            'subscription' => [
                'plan_type' => $validated['product_name'],
            ]
        ]);
    }

    public function cancel(Request $request)
    {
        // ✅ Validate incoming email
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // ✅ Find user where plan_type is still 'none' (i.e. not paid)
        $user = User::where('email', $validated['email'])
            ->where('plan_type', 'none')
            ->first();

        // ✅ Delete user if found
        if ($user) {
            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'User deleted due to canceled payment.',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'User not found or already subscribed.',
        ], 404);
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

    public function handleWebhook(Request $request): JsonResponse
    {
        $event = \Stripe\Event::constructFrom(
            $request->all()
        );

        switch ($event->type) {
            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                $subscriptionId = $invoice->subscription;

                $subscription = UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();
                if ($subscription) {
                    $subscription->update(['status' => 'active']);
                }
                break;

            case 'invoice.payment_failed':
                $invoice = $event->data->object;
                $subscriptionId = $invoice->subscription;

                $subscription = UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();
                if ($subscription) {
                    $subscription->update(['status' => 'past_due']);
                }
                break;

            case 'customer.subscription.deleted':
                $sub = $event->data->object;
                $subscription = UserSubscription::where('stripe_subscription_id', $sub->id)->first();
                if ($subscription) {
                    $subscription->update(['status' => 'canceled']);
                }
                break;
        }

        return response()->json(['status' => 'success']);
    }
}
