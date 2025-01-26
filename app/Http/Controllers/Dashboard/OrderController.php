<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Gloudemans\Shoppingcart\Facades\Cart;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Support\Facades\Http;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Session;
use Cloudinary\Configuration\Configuration as Config;
use Cloudinary\Api\Upload\UploadApi;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function pendingOrders()
    {
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100.');
        }

        $orders = Order::where('order_status', 'pending')->sortable()->paginate($row);

        return view('orders.pending-orders', [
            'orders' => $orders
        ]);
    }

    public function completeOrders()
    {
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100.');
        }

        $orders = Order::where('order_status', 'complete')->orderBy('created_at', 'desc')->paginate($row);

        return view('orders.complete-orders', [
            'orders' => $orders
        ]);
    }

    public function stockManage()
    {
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100.');
        }

        return view('stock.index', [
            'products' => Product::with(['category', 'supplier'])
                ->filter(request(['search']))
                ->sortable()
                ->paginate($row)
                ->appends(request()->query()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeOrder(Request $request)
    {
        $rules = [
            'payment_status' => 'required|string',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
        ];

        $invoice_no = IdGenerator::generate([
            'table' => 'orders',
            'field' => 'invoice_no',
            'length' => 10,
            'prefix' => 'INV-'
        ]);

        $validatedData = $request->validate($rules);
        $validatedData['order_date'] = Carbon::now()->format('Y-m-d');
        $validatedData['order_status'] = 'complete';
        $validatedData['total_products'] = Cart::count();
        $validatedData['sub_total'] = Cart::subtotal();
        $validatedData['vat'] = Cart::tax();
        $validatedData['invoice_no'] = $invoice_no;
        $validatedData['total'] = Cart::total();
        $validatedData['pay'] = Cart::total();
        $validatedData['due'] = 0;
        $validatedData['customer_name'] = $validatedData['customer_name'];
        $validatedData['customer_phone'] = $validatedData['customer_phone'];
        $validatedData['created_at'] = Carbon::now();

        $order_id = Order::insertGetId($validatedData);

        // Create Order Details
        $contents = Cart::content();
        $oDetails = array();

        foreach ($contents as $content) {
            $oDetails['order_id'] = $order_id;
            $oDetails['product_id'] = $content->id;
            $oDetails['quantity'] = $content->qty;
            $oDetails['unitcost'] = $content->price;
            $oDetails['total'] = $content->total;
            $oDetails['created_at'] = Carbon::now();

            OrderDetails::insert($oDetails);
        }

        // Delete Cart Sopping History
        Cart::destroy();

        return Redirect::route('order.invoiceDownload', $order_id)->with('success', 'Order has been created!');
    }

    /**
     * Display the specified resource.
     */
    public function orderDetails(Int $order_id)
    {
        $order = Order::where('id', $order_id)->first();
        $orderDetails = OrderDetails::with('product')
                        ->where('order_id', $order_id)
                        ->orderBy('id', 'DESC')
                        ->get();

        return view('orders.details-order', [
            'order' => $order,
            'orderDetails' => $orderDetails,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateStatus(Request $request)
    {
        $order_id = $request->id;

        // Reduce the stock
        $products = OrderDetails::where('order_id', $order_id)->get();

        foreach ($products as $product) {
            Product::where('id', $product->product_id)
                    ->update(['product_store' => DB::raw('product_store-'.$product->quantity)]);
        }

        Order::findOrFail($order_id)->update(['order_status' => 'complete']);

        return Redirect::route('order.pendingOrders')->with('success', 'Order has been completed!');
    }

    public function invoiceDownload(Int $order_id)
    {
        $order = Order::where('id', $order_id)->first();
        $orderDetails = OrderDetails::with('product')
                        ->where('order_id', $order_id)
                        ->orderBy('id', 'DESC')
                        ->get();

        // show data (only for debugging)
        return view('orders.invoice-order', [
            'order' => $order,
            'orderDetails' => $orderDetails,
        ]);
    }

    public function invoicePage (Int $order_id) {
        $order = Order::where('id', $order_id)->first();
        $orderDetails = OrderDetails::with('product')
                        ->where('order_id', $order_id)
                        ->orderBy('id', 'DESC')
                        ->get();
        // show data (only for debugging)
        return view('orders.send-invoice', [
            'order' => $order,
            'orderDetails' => $orderDetails,
        ]);
    }

    public function pendingDue()
    {
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100.');
        }

        $orders = Order::where('due', '>', '0')
            ->sortable()
            ->paginate($row);

        return view('orders.pending-due', [
            'orders' => $orders
        ]);
    }

    public function orderDueAjax(Int $id)
    {
        $order = Order::findOrFail($id);

        return response()->json($order);
    }

    public function updateDue(Request $request)
    {
        $rules = [
            'order_id' => 'required|numeric',
            'due' => 'required|numeric',
        ];

        $validatedData = $request->validate($rules);

        $order = Order::findOrFail($request->order_id);
        $mainPay = $order->pay;
        $mainDue = $order->due;

        $paid_due = $mainDue - $validatedData['due'];
        $paid_pay = $mainPay + $validatedData['due'];

        Order::findOrFail($request->order_id)->update([
            'due' => $paid_due,
            'pay' => $paid_pay,
        ]);

        return Redirect::route('order.pendingDue')->with('success', 'Due Amount Updated Successfully!');
    }

    public function postPDF($file) {
        Config::instance('cloudinary://276467372732785:rNNU3kFcL9KsIQq78QBf9BGiSKg@drqp4prsl?secure=true');
        $uploadAPI = new UploadApi();
        $response = $uploadAPI->upload($file->getRealPath(), [
            'use_filename' => true,
            'folder' => 'KNC',
        ]);
        return $response['secure_url'];
    }

    public function sendToWhatsApp(Request $request)
    {
        $customer_phone = $request->customer_phone;
        $customer_name = $request->customer_name;
        $formatted_number = preg_replace('/^0/', '+62', $customer_phone);
        $message = "🐉☕ Ni hao ".$customer_name."! ☕🐉\n\nTerima kasih telah memilih KOPI NAGA CINA sebagai teman perjalanan rasa Anda!\nBerikut kami lampirkan invoice untuk pesanan Anda. Mohon dicek, ya~ Kalau ada pertanyaan, jangan sungkan menghubungi kami! \n\n Kritik dan saran : \n https://forms.gle/Uofncx3HXcFJ5bh29 \n\n Salam hangat, \n Tim KOPI NAGA CINA ❤️ ";
 
        // Ambil file
        $file = $request->file('file');
        // up ke cloudinary
        $fileURL = $this->postPDF($file);

        // Kirim ke WhatsApp
        try {
            // Kirim file ke API WhatsApp
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://app.wapanels.com/api/create-message',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => [
                    'appkey' => '686962f0-ca15-4121-a1f4-20696d29c7a6',
                    'authkey' => 'YioPWIu2V82ekiMRXljHNj11XAUhwrjqzcKrQ0pl4thypzp1MY',
                    'to' => $formatted_number,
                    'message' => $message,
                    'file' => $fileURL,
                    'sandbox' => 'false',
                ],
            ]);

            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                $error = curl_error($curl);
                curl_close($curl);
                throw new \Exception("Error sending WhatsApp message: $error");
            }

            curl_close($curl);

            return Redirect::route('order.completeOrders')->with('success', 'Invoice berhasil dikirim ke WhatsApp!');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function cleanup($path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }


}
