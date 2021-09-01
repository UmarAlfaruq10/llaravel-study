<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\transactionItem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

class FrontendController extends Controller
{
    public function index(Request $request) {
        $products = Product::with('galleries')->latest()->get();
        return view('pages.frontend.index', compact('products'));
    }
    public function details(Request $request, $slug) {
        $product = Product::with(['galleries'])->where('slug', $slug)->firstOrFail();
        $recommendations = Product::with(['galleries'])->inRandomOrder()->limit(4)->get();

        return view('pages.frontend.details', compact('product', 'recommendations'));
    }
    public function cartAdd(Request $request, $id) {
        Cart::create([
            'user_id' => Auth::user()->id,
            'product_id' => $id
        ]);

        return redirect('cart');

    }

    public function cartDelete(Request $request, $id) {
        $cart = Cart::findOrFail($id);

        $cart->delete();

        return redirect('cart');

    }
    public function checkout(CheckoutRequest $request) {

        $data = $request->all();

        // Get Carts Data

        $carts = Cart::with(['product'])->where('user_id', Auth::user()->id)->get();

        // Add To Transaction data
        $data['user_id'] = Auth::user()->id;
        $data['total_price'] = $carts->sum('product.price');

        // Create transaction
        $transaction = Transaction::create($data);

        // Create transaction item
        foreach ($carts as $cart) {
            $items[] = transactionItem::create([
                'transaction_id' => $transaction->id,
                'user_id' => $cart->user_id,
                'product_id' => $cart->product_id
            ]);
        }

        // Delete Carts after transaction
        Cart::where('user_id', Auth::user()->id)->delete();

        // Configuration
        
        // Setup variable midtrans
        $midtrans = [
            'transaction_details' => [
                'order_id' => 'WOR-' . $transaction->id,
                'gross_amount' => (int) $transaction->total_price
            ],
            'customer_details' => [
                'first_name'=> $transaction->name,
                'email' => $transaction->email
            ],
            'enabled_payments' => ['gopay', 'bank_transfer'],
            'vtweb' => []
        ];

        // payment process

        try {
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;

            $transaction->payment_url = $paymentUrl;
            $transaction->save();

            return redirect($paymentUrl);

        }catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    public function cart(Request $request) {
        $carts = Cart::with(['product.galleries'])->where('user_id', Auth::user()->id)->get();

        return view('pages.frontend.cart', compact('carts'));
    }
    public function success(Request $request) {
        return view('pages.frontend.success');
    }
}
