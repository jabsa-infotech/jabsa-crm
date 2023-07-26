@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card p-3">
                    <div class="fw-bold">{{ $product->title }}</div>
                    <div class="d-flex">
                        @foreach ($product->categories as $category)
                            <div class="badge bg-primary text-light mx-1">{{ $category->name }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
