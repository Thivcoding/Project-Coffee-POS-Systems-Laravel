<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Sale;
use App\Services\TelegramService;
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
    public function store(Request $request, TelegramService $telegram)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,sale_id',
            'method'  => 'required|in:cash,bakong,card',
            'paid_amount' => 'nullable|numeric|min:0'
        ]);

        $sale = Sale::with('user')->findOrFail($request->sale_id);

        if ($sale->status === 'paid') {
            return response()->json([
                'message' => 'Sale already paid'
            ], 400);
        }

        DB::beginTransaction();

        try {
            if ($request->method === 'cash') {
                if ($request->paid_amount < $sale->total_amount) {
                    return response()->json(['message'=>'Paid amount is not enough'],400);
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

                $sale->update(['status' => 'paid']);
                DB::commit();

                // ================= SEND TELEGRAM FOR CASH =================
                $customerName = $sale->user->username ?? 'Walk-in Customer';

                $message = "🧾 *VANTHIV POS*\n";
                $message .= "━━━━━━━━━━━━━━━\n";
                $message .= "*Sale ID:* `{$sale->sale_id}`\n";
                $message .= "*Cashier:* {$customerName}\n";
                $message .= "*Total:* `{$payment->amount} USD`\n";
                $message .= "*Method:* {$payment->method}\n";
                $message .= "*Date:* " . now()->format('Y-m-d H:i') . "\n";
                $message .= "━━━━━━━━━━━━━━━\n";
                $message .= "✅ *Status: PAID*";

                // send message
                $telegram->sendMessage($message);

                return response()->json([
                    'message' => 'Cash payment success',
                    'payment' => $payment
                ], 201);
            }

            if ($request->method === 'bakong') {
                $payment = Payment::create([
                    'sale_id' => $sale->sale_id,
                    'method'  => 'bakong',
                    'status'  => 'pending',
                    'amount'  => $sale->total_amount,
                    'currency'=> 'KHR'
                ]);

                $merchant = new \KHQR\Models\IndividualInfo(
                    bakongAccountID: env('BAKONG_ACCOUNT'),
                    merchantName: 'VANTHIV POS',
                    merchantCity: 'Phnom Penh',
                    currency: \KHQR\Helpers\KHQRData::CURRENCY_KHR,
                    amount: $payment->amount
                );

                $bakong = new \KHQR\BakongKHQR(env('BAKONG_TOKEN'));
                $qrResponse = $bakong->generateIndividual($merchant);

                if (!isset($qrResponse->data['qr'])) {
                    throw new \Exception('Cannot generate KHQR');
                }

                $payment->update([
                    'qr_string'     => $qrResponse->data['qr'],
                    'bakong_txn_id' => $qrResponse->data['md5']
                ]);

                DB::commit();

                // ================= SEND TELEGRAM FOR BAKONG =================
                $cashier = $sale->user->username ?? 'Unknown';

                $message = "🧾 *VANTHIV POS*\n";
                $message .= "━━━━━━━━━━━━━━━\n";
                $message .= "*Sale ID:* `{$sale->sale_id}`\n";
                $message .= "*Cashier:* {$cashier}\n";
                $message .= "*Total:* `{$payment->amount} KHR`\n";
                $message .= "*Payment:* BAKONG\n";
                $message .= "*Date:* " . now()->format('Y-m-d H:i') . "\n";
                $message .= "━━━━━━━━━━━━━━━\n";
                $message .= "📲 *Scan QR To Pay*\n";
                $message .= "🕒 *Status:* Pending";

                $telegram->sendMessage($message);

                return response()->json([
                    'message' => 'Bakong QR generated',
                    'payment' => $payment,
                    'qr'      => $payment->qr_string
                ], 201);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check Bakong payment status
     */
    public function checkBakong(Payment $payment, TelegramService $telegram)
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

                // Prevent duplicate notification
                if ($payment->status !== 'paid') {

                    $payment->update([
                        'status'      => 'paid',
                        'paid_amount' => $payment->amount
                    ]);

                    $payment->sale->update(['status' => 'paid']);

                    $sale = $payment->sale()->with('user')->first();
                    $cashier = $sale->user->username ?? 'Unknown';

                    // ================= SEND TELEGRAM =================
                    $message = "🧾 *VANTHIV POS*\n";
                    $message .= "━━━━━━━━━━━━━━━\n";
                    $message .= "*Sale ID:* `{$sale->sale_id}`\n";
                    $message .= "*Cashier:* {$cashier}\n";
                    $message .= "*Total:* `{$payment->amount} KHR`\n";
                    $message .= "*Payment:* BAKONG\n";
                    $message .= "*Date:* " . now()->format('Y-m-d H:i') . "\n";
                    $message .= "━━━━━━━━━━━━━━━\n";
                    $message .= "✅ *Status: PAID* 🎉";

                    $telegram->sendMessage($message);
                }
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
