@extends('layout.app')


@section('content-main')
    @include('flash')
    <a href="{{  route('init') }}">Get Token</a>
@endsection
