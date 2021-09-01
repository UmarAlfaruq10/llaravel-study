<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Notification;

class MidtransController extends Controller
{
    public function callback() {

        // Set konfigurasi midtrans

        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // Buat Instance midtrans konfigurasi

        $notifaction = new Notification();

        // Assign ke variable untuk memudahkan coding
        $status     = $notifaction->transaction_id;
        $type       = $notifaction->payment_type;
        $fraud      = $notifaction->fraus_status;
        $order_id   = $notifaction->order_id;

        // Get Transaction id

        $order = explode('-', $order_id);

        // Cari transaksi berdasarkan ID
        $transaction = Transaction::findOrFail($order[1]);

        // Handle notification status midtrans

        if($status =='capture') {
            if($type == 'credit_card') {
                if($fraud == 'challenge') {
                    $transaction->status = 'PENDING';
                }else{
                    $transaction->status = 'SUCCESS';
                }
            }
        }
        else if($status == 'settlement') {
            $transaction->status = 'SUCCESS';
        }
        else if($status == 'pending') {
            $transaction->status = 'PENDING';
        }
        else if($status == 'deny') {
            $transaction->status = 'PENDING';
        }
        else if($status == 'expire') {
            $transaction->status = 'CANCELLED';
        }
        else if($status == 'cancel') {
            $transaction->status = 'CANCELLED';
        }

        // Simpan transaksi

        $transaction->save();

        // return response untuk midtrans

        return response()->json([
            'meta' => [
                'code' => '201',
                'message' => 'Midtrans Notification Success'
            ]
            ]);

    }
}
