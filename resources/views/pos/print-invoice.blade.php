<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>GNC POS</title>

        <!-- Favicon -->
        <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}"/>
        <link rel="stylesheet" href="{{ asset('assets/css/backend-plugin.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/backend.css?v=1.0.0') }}">
        <link href="https://cdn.jsdelivr.net/gh/hung1001/font-awesome-pro@4cac1a6/css/all.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/remixicon/fonts/remixicon.css') }}">
    </head>
<body>

    <!-- Wrapper Start -->
    <div class="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-block">
                        <div class="card-header d-flex justify-content-between bg-primary">
                            <div class="iq-header-title">
                                <h4 class="card-title mb-0">Kopi Naga Cina</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <img src="{{ asset('assets/images/logo.png') }}" class="logo-invoice img-fluid mb-3">
                                    <h5 class="mb-3">Hello, {{ $customer->name }}</h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-responsive-sm">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Order Date</th>
                                                    <th scope="col">Order Status</th>
                                                    <th scope="col">Detail Information</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>{{ Carbon\Carbon::now()->format('M d, Y') }}</td>
                                                    <td><span class="badge badge-success">Paid</span></td>
                                                    <td>
                                                        <p class="mb-0">
                                                            Phone: {{ $customer->phone }}
                                                        </p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <h5 class="mb-3">Order Summary</h5>
                                    <div class="table-responsive-lg">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" scope="col">#</th>
                                                    <th scope="col">Item</th>
                                                    <th class="text-center" scope="col">Quantity</th>
                                                    <th class="text-center" scope="col">Price</th>
                                                    <th class="text-center" scope="col">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($content as $item)
                                                <tr>
                                                    <th class="text-center" scope="row">{{ $loop->iteration }}</th>
                                                    <td>
                                                        <h6 class="mb-0">{{ $item->name }}</h6>
                                                    </td>
                                                    <td class="text-center">{{ $item->qty }}</td>
                                                    <td class="text-center">Rp.{{ number_format($item->price) }}</td>
                                                    <td class="text-center"><b>Rp.{{ number_format($item->subtotal) }}</b></td>
                                                </tr>

                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <b class="text-danger">Notes:</b>
                                    <p class="mb-0">Terimakasih telah berkunjung ke Kopi Guild Naga Cina. Ditunggu Order Selanjutnya :) </p>
                                </div>
                            </div>
                            <div class="row mt-4 mb-3">
                                <div class="offset-lg-8 col-lg-4">
                                    <div class="or-detail rounded">
                                        <div class="p-3">
                                            <h5 class="mb-3">Order Details</h5>
                                        </div>
                                        <div class="ttl-amt py-2 px-3 d-flex justify-content-between align-items-center">
                                            <h6>Total</h6>
                                            <h3 class="text-primary font-weight-700">Rp.{{ number_format(Cart::total()) }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Wrapper End-->

    <script>
    window.addEventListener("load", (event) => {
        window.print();
    });
    </script>
</body>
</html>
