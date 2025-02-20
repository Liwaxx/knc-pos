<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(){
        $topSelling = DB::table('order_details')
        ->join('products', 'order_details.product_id', '=', 'products.id') // Join dengan tabel products
        ->select('order_details.product_id', 'products.product_name', DB::raw('SUM(order_details.quantity) as quantity')) // Pilih kolom yang diperlukan
        ->groupBy('order_details.product_id', 'products.product_name') // Pastikan semua kolom non-aggregat disertakan dalam GROUP BY
        ->orderBy('quantity', 'DESC') // Urutkan berdasarkan total
        ->take(5) // Ambil 5 produk terlaris
        ->get();



        $topCustomer = DB::table('orders')
        ->select('customer_name', DB::raw('SUM(total) as total'))
        ->groupBy('customer_name')
        ->orderBy('total', 'DESC')
        ->take(5)
        ->get();

        $dialySales = DB::table('orders')
        ->select('order_date', DB::raw('SUM(total) as total'))
        ->groupBy('order_date')
        ->orderBy('order_date', 'DESC')
        ->take(5)
        ->get();
    
        $monthlySales = DB::table('orders')
        ->select(DB::raw("DATE_FORMAT(order_date, '%Y-%m') as month"), DB::raw('SUM(total) as total'))
        ->groupBy('month')
        ->orderBy('month', 'DESC')
        ->take(5)
        ->get();

        return view('dashboard.index', [
            'total_paid' => Order::sum('pay'),
            'complete_orders' => Order::where('order_status', 'complete')->get(),
            'topSelling' => $topSelling,
            'topCustomer' => $topCustomer,
            'dialySales' => $dialySales,
            'monthlySales' => $monthlySales
        ]);
    }
}
