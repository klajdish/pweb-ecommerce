<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Order as OrderModel;
use App\Models\Coupon;
use App\Models\OrderItem;
use App\Models\OrderAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PragmaRX\Countries\Package\Countries;


class Order extends Controller
{

    public function checkout(Request $request){


        $user = User::find(Session::get('loginId'));
        $countries = Countries::all()->pluck('name.common');

        if ($user) {
            if(!$user->cart || ($user->cart && $user->cart->cartItems()->count()== 0)){
                return redirect('cart')->with('fail','Your cart is empty!');
            }
            $cartItems = $user->cart->cartItems;

            $products = $cartItems->map(function ($cartItem) {
                return $cartItem->product;
            });

            $coupon = null;
            if($request->coupon){
                $couponId = $request->coupon;
                $coupon = Coupon::where('id', $couponId)->first();
            }

            return view('checkout',compact('coupon','products','user', 'countries'));

        } else {
            return back()->with('fail', 'Login to proceed');
        }
    }

    public function getCities(Request $request)
    {
        $country = $request->input('country');
        $cities = Countries::where('name.common', $country)->first()->hydrate('cities')->cities->pluck('name');

        return response()->json($cities);
    }

    public function addOrder(Request $request)
    {
        $user = User::where('id', Session::get('loginId'))->first();


        if($user){

            $validatedData = [];
            if(!$request->has('user_address')) {

                $validatedData = $request->validate([
                    'state' => 'required|string|max:255',
                    'city' => 'required|string|max:255',
                    'street' => 'required|string|max:255',
                    'zip_code' => 'required|string|max:10',
                    'payment_method' => 'required'
                ], [
                    'state.required' => 'The state field is required.',
                    'city.required' => 'The city field is required.',
                    'street.required' => 'The street field is required.',
                    'zip_code.required' => 'The ZIP code field is required.',
                    'payment_method.required' => 'The Payment method is required'
                ]);
            }else {
                $validatedData = $request->validate([
                    'payment_method' => 'required'
                ], [
                    'payment_method.required' => 'The Payment method is required'
                ]);
            }


            // get cart items
            $cartItems = $user->cart->cartItems;

            $coupon = null;
            if($request->coupon){
                $coupon = Coupon::where('id', $request->coupon)->first();
            }

            $orderTotal = $this->getOrderTotal($cartItems, $coupon);

            if (strtolower($validatedData['payment_method'])  === 'paypal') {
                // Set up PayPal client
                $environment = new SandboxEnvironment(env('PAYPAL_CLIENT_ID'), env('PAYPAL_CLIENT_SECRET'));
                $client = new PayPalHttpClient($environment);

                // Create PayPal order
                $orderRequest = new OrdersCreateRequest();
                $orderRequest->prefer('return=representation');
                $orderRequest->body = [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'amount' => [
                                'currency_code' => 'USD',
                                'value' => number_format($orderTotal, 2, '.', ''), // Set the total order amount here
                            ],
                        ],
                    ],
                    'application_context' => [
                        'cancel_url' => route('payment.cancel'),
                        'return_url' => route('payment.success'),
                    ],
                ];
                $response = $client->execute($orderRequest);

                // Handle the PayPal response and redirect to the PayPal payment page
                if ($response->statusCode === 201 || $response->statusCode === 200) {
                    $approvalLink = collect($response->result->links)->firstWhere('rel', 'approve');
                    if ($approvalLink) {

                        $userAddress = $user->addresses()->first();

                        Session::flash('orderData', [
                            'cartItems' => $cartItems,
                            'orderTotal' => $orderTotal,
                            'coupon' => $coupon,
                            'validatedData' => $validatedData,
                            'userAddress' => $userAddress
                        ]);

                        return redirect()->away($approvalLink->href);
                    }
                }

                // If there's an error with PayPal, redirect to the failure page
                return redirect()->route('payment.cancel')->with('fail', 'Failed to initiate PayPal payment.');
            }



        }
        return redirect('/cart')>with('fail', 'Something went wrong');
    }


    public function cancel()
    {
        return redirect('/cart')->with('fail', 'Payment canceled.');
    }


    public function success()
    {
        $orderData = Session::get('orderData');

        $orderTotal = $orderData['orderTotal'];
        $cartItems = $orderData['cartItems'];
        $coupon = $orderData['coupon'];
        $validatedData = $orderData['validatedData'];
        $userAddress = $orderData['userAddress'];


        // save OrderAddress model into database
        if($userAddress) {
            $orderAddress = OrderAddress::create([
                'state' => $userAddress->state,
                'city' => $userAddress->city,
                'street' => $userAddress->street,
                'zip_code' => $userAddress->zip_code,
            ]);
        }else {
            $addressData = $validatedData;
            unset($addressData['payment_method']);
            $orderAddress = OrderAddress::create($addressData);
        }


        // create Order model
        $order = new OrderModel;
        $order->user_id = Session::get('loginId');
        $order->address_id =  $orderAddress->id;
        $order->status_id = 1;
        $order->payment_method = $validatedData['payment_method'];
        $order->total = $orderTotal;


        //  save order model to get the id
        $order->save();

        // create/save Order Item model for the current order
        foreach ($cartItems as $cartItem){
            $product = $cartItem->product;

            $orderItem = new OrderItem;
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $product->id;
            $orderItem->quantity = $cartItem->quantity;
            $orderItem->price =  number_format($coupon ? $product->price - (($product->price / 100) * $coupon->discount) : $product->price, 2, '.', '');
            $orderItem->save();

            $product->quantity -= $cartItem->quantity;
            $product->save();

            $cartItem->delete();
        }

        return redirect('/cart')->with('success', 'Your order was placed succesfully! Please wait to be verifed and completed!');
    }

    public function getOrderTotal($cartItems, $coupon = null)
    {
        $orderTotal = 0;

        foreach ($cartItems as $cartItem){
            $product = $cartItem->product;
            $unitPrice = $coupon ? $product->price - (($product->price / 100) * $coupon->discount) : $product->price;

            $orderTotal = $orderTotal + ($unitPrice * $cartItem->quantity);
        }

        return $orderTotal;
    }
}
