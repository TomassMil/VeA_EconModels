@extends('layouts.app-shell')

@section('middle')
    @include('instruments.partials._screener-middle', ['routeName' => 'fundamentals.show'])
@endsection

@section('right')
    <div id="right-column-content" data-mode="fundamental">
        @include('instruments.partials._right-fundamental')
    </div>
@endsection
