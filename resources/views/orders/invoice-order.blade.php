<!DOCTYPE html>
<html lang="zxx">
<head>
    <title>POS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">

    <!-- External CSS libraries -->
    <link rel="stylesheet" href="{{ asset('assets/css/backend-plugin.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/backend.css?v=1.0.0') }}">
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/invoice/css/bootstrap.min.css') }}">

    <!-- Google fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Custom Stylesheet -->
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/invoice/css/style.css') }}">
</head>
<body>
    <div class="invoice-16 invoice-content">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="invoice-inner-9" id="invoice_wrapper">
                        <div class="invoice-top">
                            <div class="row">
                                <div class="col-lg-6 col-sm-6">
                                    <div class="logo">
                                        <img class="logo" src="{{ asset('assets/images/logo.png') }}" alt="logo">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-6">
                                    <div class="invoice">
                                        <h1>#<span>{{ $order->invoice_no }}</span></h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="invoice-info">
                            <div class="row">
                                <div class="col-sm-6 mb-50">
                                    <div class="invoice-number">
                                        <h4 class="inv-title-1">Invoice date:</h4>
                                        <p class="invo-addr-1">
                                            {{ $order->order_date }}
                                        </p>
                                    </div>
                                </div>
                                <div class="col-sm-6 text-end mb-50">
                                    <h4 class="inv-title-1">Kopi Naga Cina</h4>
                                    <p class="inv-from-1">kopinagacina@gmail.com</p>
                                    <p class="inv-from-2">Malang, Indonesia</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 mb-50">
                                    <h4 class="inv-title-1">Customer</h4>
                                    <p class="inv-from-1">{{ $order->customer_name }}</p>
                                    <p class="inv-from-1">{{ $order->customer_phone }}</p>
                                </div>
                                <div class="col-sm-6 text-end mb-50">
                                    <h4 class="inv-title-1">Details</h4>
                                    <p class="inv-from-1">Payment Status: {{ $order->payment_status }}</p>
                                    <p class="inv-from-1">Total Pay: Rp.{{ number_format($order->pay) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="order-summary">
                            <div class="table-outer">
                                <table class="default-table invoice-table">
                                    <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($orderDetails as $item)
                                        <tr>
                                            <td>{{ $item->product->product_name }}</td>
                                            <td>Rp.{{ number_format($item->unitcost) }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>Rp.{{ number_format($item->total) }}</td>
                                        </tr>
                                        @endforeach
                                        <tr>
                                            <td><strong class="text-danger">Total</strong></td>
                                            <td></td>
                                            <td></td>
                                            <td><strong class="text-danger">Rp.{{number_format($order->total)}}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <img class="w-100" src="{{ asset('assets/images/Footer-Invoice.png') }}" alt="logo">
                        </div>
                    </div>

                    <div class="invoice-btn-section clearfix d-print-none">
                        <a id="invoice_download_btn" class="btn btn-lg btn-download" style="color:white;font-weight:500">
                            Download Invoice
                        </a>
                        <button type="button" data-toggle="modal" data-target="#exampleModal" class="btn btn-lg btn-print">
                            Send Invoice to Customer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Upload Invoice and Send</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <form action="{{ route('order.sendToWhatsApp') }}" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                    @csrf
                    <input type="hidden" name="customer_phone" value="{{ $order->customer_phone }}">
                    <input type="hidden" name="customer_name" value="{{ $order->customer_name }}">

                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="payment_status"><b>Choose file</b> : </label> <br>
                            <input type="file" name="file">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" id="submitBtn" class="btn btn-primary">Send Invoice</button>
                </div>
            </div>
        </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.getElementById('submitBtn');

            form.addEventListener('submit', function() {
                submitBtn.disabled = true; // Disable button
                submitBtn.innerHTML = 'Sending...'; // Optional: Ubah teks
            });
        });
    </script>
    <script src="{{ asset('assets/js/backend-bundle.min.js') }}"></script>

    <script src="{{ asset('assets/invoice/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/invoice/js/jspdf.min.js') }}"></script>
    <script src="{{ asset('assets/invoice/js/html2canvas.js') }}"></script>
    <script src="{{ asset('assets/invoice/js/app.js') }}"></script>
</body>
</html>
