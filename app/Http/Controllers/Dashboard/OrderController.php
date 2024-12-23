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

        $orders = Order::where('order_status', 'complete')->sortable()->paginate($row);

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

        return Redirect::route('dashboard')->with('success', 'Order has been created!');
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

    // public function generateInvoiceImage($id)
    // {
    //     $fileName = "invoice_$id.png";

    //     // Tentukan path lengkap ke public storage
    //     $path = public_path('storage/' . $fileName);

    //     // URL yang akan diakses di API eksternal
    //     // $url = 'https://api.ryzendesu.vip/api/tool/ssweb?url='.'https://b7ad-2001-448a-c0a0-dc6-5c9e-8b46-dda-95b.ngrok-free.app/orders/invoice/invoice-page/'.$id.'&mode=full';
        
    //     // Mengencode URL yang akan diproses
    //     // $encodedUrl = route('order.invoicePage', $id);
    //     // $encodedUrl = urlencode('https://b7ad-2001-448a-c0a0-dc6-5c9e-8b46-dda-95b.ngrok-free.app/orders/invoice/invoice-page/6');
        
    //     // URL yang akan diakses
    //     $url = 'https://api.ryzendesu.vip/api/tool/ssweb?url=https%3A%2F%2Fb7ad-2001-448a-c0a0-dc6-5c9e-8b46-dda-95b.ngrok-free.app%2Forders%2Finvoice%2Finvoice-page%2F6&mode=full';

    //     // Inisialisasi cURL
    //     $ch = curl_init();

    //     curl_setopt($ch, CURLOPT_URL, urldecode($url));
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //         'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36',
    //         'Accept: image/png',
    //     ]);

    //     $response = curl_exec($ch);

    //     // Cek apakah ada error
    //     if(curl_errno($ch)) {
    //         echo 'Error:' . curl_error($ch); // Tampilkan error jika ada
    //     } else {
    //         $filePath = public_path("storage/invoice_$id.png");
    //         file_put_contents($filePath, $response);
    //         dd($response);
    //     }

    //     // Tutup cURL
    //     curl_close($ch);
    //     return $fileName;
    // }

    // public function sendToWhatsApp($id,$customer_number)
    // {
    //     $formattedNumber = str_replace('0', '+62', $customer_number);
    //     $fileName = $this->generateInvoiceImage($id);
    //     $fileUrl = asset('storage/' . $fileName);

    //     // Kirim file gambar ke API WhatsApp
    //     $curl = curl_init();

    //     curl_setopt_array($curl, array(
    //             CURLOPT_URL => 'https://app.wapanels.com/api/create-message',
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_ENCODING => '',
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 0,
    //             CURLOPT_FOLLOWLOCATION => true,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => 'POST',
    //             CURLOPT_POSTFIELDS => array(
    //             'appkey' => '686962f0-ca15-4121-a1f4-20696d29c7a6',
    //             'authkey' => 'YioPWIu2V82ekiMRXljHNj11XAUhwrjqzcKrQ0pl4thypzp1MY',
    //             'to' => $formattedNumber,
    //             'message' => 'Terima kasih telah ngopi di KNC. Berikut adalah Inovoice anda.',
    //             'file' => $fileUrl,
    //             'sandbox' => 'false'
    //         ),
    //     ));

    //     $response = curl_exec($curl);
    //     curl_close($curl);

    //     // Hapus file sementara setelah pengiriman berhasil
    //     $this->cleanup(storage_path("app/public/$fileName"));
    //     // dd($response);
    //     return $response->json();
    // }

    // public function cleanup($path)
    // {
    //     if (file_exists($path)) {
    //         unlink($path);
    //     }
    // }

    public function postImg($img) {
        Config::instance('cloudinary://443434587856543:6TqAFFTUDJE761TJCGhNCNEK12E@dakzsoxtt?secure=true');
        $uploadAPI = new UploadApi();
        $response = $uploadAPI->upload($img, [
            'use_filename' => true,
            'folder' => 'KNC',
        ]);
        return $response;
    }

    public function generateInvoiceImage($id)
    {
        $fileName = "invoice_$id.png";
        $path = public_path('storage/' . $fileName);

        $urlEncoded = urlencode('https://fz4rmdqx-8000.asse.devtunnels.ms/orders/invoice/invoice-page/'.$id);

        // URL API untuk mengambil screenshot
        $url = 'https://api.ryzendesu.vip/api/tool/ssweb?url='.$urlEncoded.'&mode=full';

        // Inisialisasi cURL
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, urldecode($url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36',
            'Accept: image/png',
        ]);

        $response = curl_exec($ch);
        // Cek apakah ada error
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Error generating invoice image: $error");
        }

        file_put_contents($path, $response);
        $cloudinaryResponse = $this->postImg(storage_path('app/public/' . $fileName));

        curl_close($ch);

        return $cloudinaryResponse;
    }

    public function sendToWhatsApp($id, $customer_number)
    {
        try {
            // Generate invoice image
            $fileUrl = $this->generateInvoiceImage($id);

            // dd($fileUrl);
            // Format nomor WhatsApp
            $formattedNumber = preg_replace('/^0/', '+62', $customer_number);

            // Kirim file ke API WhatsApp
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://app.wapanels.com/api/create-message',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => [
                    'appkey' => '686962f0-ca15-4121-a1f4-20696d29c7a6',
                    'authkey' => 'YioPWIu2V82ekiMRXljHNj11XAUhwrjqzcKrQ0pl4thypzp1MY',
                    'to' => $formattedNumber,
                    'message' => 'Terima kasih telah ngopi di KNC. Berikut adalah Inovoice anda.',
                    'file' => $fileUrl['url'],
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

            return response()->json([
                'success' => true,
                'message' => 'Invoice berhasil dikirim ke WhatsApp!',
                'response' => json_decode($response, true),
            ]);
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
