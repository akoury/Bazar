@extends('layouts.app')

@section('title', $brand->name)

@section('content')
    <h1>{{ $brand->name }}</h1>
    <h2>{{ $brand->slogan }}</h2>
    @auth
        <a href="{{ route('products.create', $brand) }}">Add a product</a>
    @endauth
@endsection