<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $userId = $request->input('user_id');
        $orders = Order::query();

        $orders->when($userId, function ($query) use ($userId) {
            return $query->where('user_id', '=', $userId);
        });

        return response()->json([
            'status' => 'SUCCESS',
            'data' => $orders->get()
        ]);
    }
    public function error()
    {
        return 'PAYMENT ERROR';
    }
    public function unfinish()
    {
        return 'ORDER UNFINISHED';
    }
    public function finish()
    {
        return 'ORDER SUCCESS';
    }
    public function create(Request $request)
    {
        $user = $request->input('user');
        $course = $request->input('course');

        $order = Order::create([
            'user_id' => $user['id'],
            'course_id' => $course['id']
        ]);

        $transactionDetails = [
            'order_id' => $order->id,
            'gross_amount' => $course['price']
        ];

        $itemDetails = [
            [
                'id' => $course['id'],
                'price' => $course['price'],
                'quantity' => 1,
                'name' => $course['name'],
                'brand' => 'Lessons',
                'category' => 'Online Course'
            ]
        ];

        $customerDetails = [
            'first_name' => $user['name'],
            'email' => $user['email']
        ];

        $midtransParams = [
            'transaction_details' => $transactionDetails,
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails
        ];

        $midtransSnapUrl = $this->getMidtransSnapUrl($midtransParams);

        $order->snap_url = $midtransSnapUrl;
        $order->metadata = [
            'course_id' => $course['id'],
            'course_price' => $course['price'],
            'course_name' => $course['name'],
            'course_thumbnail' => $course['thumbnail'],
            'course_level' => $course['level'],
        ];
        $order->save();

        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Order success',
            'data' => $order
        ]);
    }

    private function getMidtransSnapUrl($params)
    {
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_PRODUCTION');
        \Midtrans\Config::$isSanitized = (bool) env('MIDTRANS_SANITIZED');
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_3DS');

        $snapUrl = \Midtrans\Snap::createTransaction($params)
            ->redirect_url;

        return $snapUrl;
    }
}
