<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Sale;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

class PaymentController extends Controller
{
    //
    /**
     * Create payment (cash / bakong)
     */
    public function store(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,sale_id',
            'method'  => 'required|in:cash,bakong,card',
            'paid_amount' => 'nullable|numeric|min:0'
        ]);

        $sale = Sale::findOrFail($request->sale_id);

        // Sale already paid
        if ($sale->status === 'paid') {
            return response()->json([
                'message' => 'Sale already paid'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // ===================== CASH PAYMENT =====================
            if ($request->method === 'cash') {

                if ($request->paid_amount < $sale->total_amount) {
                    return response()->json([
                        'message' => 'Paid amount is not enough'
                    ], 400);
                }

                $payment = Payment::create([
                    'sale_id'       => $sale->sale_id,
                    'method'        => 'cash',
                    'status'        => 'paid',
                    'amount'        => $sale->total_amount,
                    'paid_amount'   => $request->paid_amount,
                    'change_amount' => $request->paid_amount - $sale->total_amount,
                    'currency'      => 'USD'
                ]);

                // update sale
                $sale->update(['status' => 'paid']);

                DB::commit();

                return response()->json([
                    'message' => 'Cash payment success',
                    'payment' => $payment
                ], 201);
            }

            // ===================== BAKONG PAYMENT =====================
            if ($request->method === 'bakong') {

                $payment = Payment::create([
                    'sale_id' => $sale->sale_id,
                    'method'  => 'bakong',
                    'status'  => 'pending',
                    'amount'  => $sale->total_amount,
                    'currency'=> 'KHR'
                ]);

                // Generate KHQR
                $merchant = new IndividualInfo(
                    bakongAccountID: env('BAKONG_ACCOUNT'),
                    merchantName: 'VANTHIV POS',
                    merchantCity: 'Phnom Penh',
                    currency: KHQRData::CURRENCY_KHR,
                    amount: $payment->amount
                );

                $bakong = new BakongKHQR(env('BAKONG_TOKEN'));
                $qrResponse = $bakong->generateIndividual($merchant);

                if (!isset($qrResponse->data['qr'])) {
                    throw new Exception('Cannot generate KHQR');
                }

                $payment->update([
                    'qr_string'     => $qrResponse->data['qr'],
                    'bakong_txn_id' => $qrResponse->data['md5']
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Bakong QR generated',
                    'payment' => $payment,
                    'qr'      => $payment->qr_string
                ], 201);
            }

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check Bakong payment status
     */
    public function checkBakong(Payment $payment)
    {
        if ($payment->method !== 'bakong') {
            return response()->json([
                'message' => 'Invalid payment method'
            ], 400);
        }

        try {
            $bakong = new BakongKHQR(env('BAKONG_TOKEN'));
            $result = $bakong->checkTransactionByMD5($payment->bakong_txn_id);

            if (($result['responseCode'] ?? 1) === 0) {
                $payment->update([
                    'status'      => 'paid',
                    'paid_amount' => $payment->amount
                ]);

                $payment->sale->update(['status' => 'paid']);
            }

            return response()->json([
                'success' => true,
                'data'    => $result,
                'payment' => $payment
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel payment
     */
    public function cancel(Payment $payment)
    {
        if ($payment->status === 'paid') {
            return response()->json([
                'message' => 'Cannot cancel paid payment'
            ], 400);
        }

        $payment->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Payment cancelled',
            'payment' => $payment
        ]);
    }
}
