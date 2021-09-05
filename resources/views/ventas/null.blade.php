@extends('layouts.app')

@section('content')

<ventas-null v-bind:ventas="{{$ventas}}" v-bind:piso="{{$piso_venta}}" />
{{--<ventas-null />--}}
@endsection
