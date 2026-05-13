@extends('layouts.app-shell')

@section('middle')
    @include('instruments.partials._screener-middle', ['routeName' => 'technical.show'])
@endsection

@section('right')
    <div id="right-column-content" data-mode="technical">
        @include('instruments.partials._right-technical')
    </div>
@endsection
