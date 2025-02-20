@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
        @if (session()->has('success'))
            <div class="alert text-white bg-success" role="alert">
                <div class="iq-alert-text">{{ session('success') }}</div>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <i class="ri-close-line"></i>
                </button>
            </div>
        @endif
        </div>
        <div class="col-lg-4">
            <div class="card card-transparent card-block card-stretch card-height border-none">
                <div class="card-body p-0 mt-lg-2 mt-0">
                    <h3 class="mb-3">Hi {{ auth()->user()->name }}, Good Morning</h3>
                    <p class="mb-0 mr-4">Your dashboard gives you views of key performance or business process.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="row">
                <div class="col-lg-6 col-md-6">
                    <div class="card card-block card-stretch card-height">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4 card-total-sale">
                                <div class="icon iq-icon-box-2 bg-info-light">
                                    <img src="../assets/images/product/1.png" class="img-fluid" alt="image">
                                </div>
                                <div>
                                    <p class="mb-2">Total Paid</p>
                                    <h4>Rp. {{ number_format($total_paid) }}</h4>
                                </div>
                            </div>
                            <div class="iq-progress-bar mt-2">
                                <span class="bg-info iq-progress progress-1" data-percent="85">
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="card  card-block card-stretch card-height">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4 card-total-sale">
                                <div class="icon iq-icon-box-2 bg-success-light">
                                    <img src="../assets/images/product/3.png" class="img-fluid" alt="image">
                                </div>
                                <div>
                                    <p class="mb-2">Complete Orders</p>
                                    <h4>{{ count($complete_orders) }}</h4>
                                </div>
                            </div>
                            <div class="iq-progress-bar mt-2">
                                <span class="bg-success iq-progress progress-1" data-percent="75">
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-block card-stretch mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title mb-0">Top Selling Products</h4>
                    </div>
                </div>
                <div class="card-content p-3">
                    <ol class="p-0">
                        @foreach ($topSelling as $product)
                            <li class="my-3 d-flex align-items-center justify-content-between"><span>{{ $loop->index+1 }}. {{ $product->product_name }}</span><span>{{ $product->quantity }}</span></li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-block card-stretch mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title mb-0">Dialy Sales</h4>
                    </div>
                </div>
                <div class="card-content p-3">
                    <ol class="p-0">
                        @foreach ($dialySales as $sale)
                            <li class="my-3 d-flex align-items-center justify-content-between"><span>{{ $loop->index+1 }}. {{ \Carbon\Carbon::parse($sale->order_date)->translatedFormat('d F Y') }}</span><span>Rp. {{ number_format($sale->total) }}</span></li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-block card-stretch mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title mb-0">Sales per Month</h4>
                    </div>
                </div>
                <div class="card-content p-3">
                    <ol class="p-0">
                        @foreach ($monthlySales as $monthlySale)
                            <li class="my-3 d-flex align-items-center justify-content-between"><span>{{ $loop->index+1 }}. {{  \Carbon\Carbon::parse($monthlySale->month)->translatedFormat('F Y') }}</span><span>Rp. {{ number_format($monthlySale->total) }}</span></li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-block card-stretch mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title mb-0">Top Spending Customer</h4>
                    </div>
                </div>
                <div class="card-content p-3">
                    <ol class="p-0">
                        @foreach ($topCustomer as $customer)
                            <li class="my-3 d-flex align-items-center justify-content-between"><span>{{ $loop->index+1 }}. {{ $customer->customer_name }}</span><span>Rp. {{ number_format($customer->total) }}</span></li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- Page end  -->
</div>
@endsection

@section('specificpagescripts')
<!-- Table Treeview JavaScript -->
<script src="{{ asset('assets/js/table-treeview.js') }}"></script>
<!-- Chart Custom JavaScript -->
<script src="{{ asset('assets/js/customizer.js') }}"></script>
<!-- Chart Custom JavaScript -->
<script async src="{{ asset('assets/js/chart-custom.js') }}"></script>
@endsection
