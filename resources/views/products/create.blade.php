@extends('layouts.app')

@section('title', 'Create a Product')

@section('content')
    <div class="p-4 container mx-auto">
        <product-create brand-id="{{ $brand->id }}"></product-create>
    </div>
@endsection