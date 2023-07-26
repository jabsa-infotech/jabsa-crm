@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <h2>Products for category <span class="fw-light">{{ $category->name }}</span></h2>
            @foreach ($category->products as $product)
                <div class="col-12 col-md-4 col-lg-3">
                    <div class="card p-3">
                        <img src="https://rukminim2.flixcart.com/image/832/832/kwfaj680/sandal/t/3/x/7-1792059-41-5-crocs-grey-original-imag93m7apqgggs4.jpeg?q=70" alt="" class="img-fluid">
                        <div class="">{{ $product->title }}</div>
                        <div class="d-flex">
                            ₹ {{ $product->price }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
