<?php

namespace App\Http\Controllers;

use App\Order;
use App\PaymentLog;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function midtransHandler(Request $request)
    {
        $data = $request->all();

        $signatureKey = $data['signature_key'];
        $orderId = $data['order_id'];
        $statusCode = $data['status_code'];
        $grossAmount = $data['gross_amount'];
        $serverKey = env('MIDTRANS_SERVER_KEY');

        $mySignatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $transactionStatus = $data['transaction_status'];
        $paymentType = $data['payment_type'];
        $fraudStatus = $data['fraud_status'];

        if ($signatureKey !== $mySignatureKey) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'invalid signature'
            ], 404);
        }

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'order not found'
            ], 404);
        }

        if ($order->status === 'success') {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'operation not permitted'
            ], 405);
        }

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $order->status = 'challenge';
            } else if ($fraudStatus == 'accept') {
                $order->status = 'success';
            }
        } else if ($transactionStatus == 'settlement') {
            $order->status = 'success';
        } else if (
            $transactionStatus == 'cancel' ||
            $transactionStatus == 'deny' ||
            $transactionStatus == 'expire'
        ) {
            $order->status = 'failure';
        } else if ($transactionStatus == 'pending') {
            $order->status = 'pending';
        }

        $logData = [
            'status' => $transactionStatus,
            'raw_response' => json_encode($data),
            'order_id' => $orderId,
            'payment_type' => $paymentType,
        ];

        PaymentLog::create($logData);
        $order->save();

        // if ($order->status === 'success') {
        $premiumAccess = createPremiumAccess([
            'user_id' => $order->user_id,
            'course_id' => $order->course_id
        ]);
        // }

        return response()->json([
            'status' => 'SUCCESS',
            'order' => $premiumAccess
        ], 200);
    }
}
