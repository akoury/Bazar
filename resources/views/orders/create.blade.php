@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    <h1>Checkout</h1>
    @foreach($products as $product)
        <h1>
            {{ $cart->findProduct($product)['quantity'] }} of
            {{ $product->name }} ${{ $product->price() }}
        </h1>
    @endforeach
    <cart-checkout total-price="{{ $total }}" email="{{ optional(auth()->user())->email }}"></cart-checkout>
@endsection

@push('scripts')
    <meta name="turbolinks-visit-control" content="reload">
    <script src="https://checkout.stripe.com/checkout.js"></script>
    <script>
        var App = {
            stripeKey: '{{ config('services.stripe.key') }}',
        }
    </script>
@endpush