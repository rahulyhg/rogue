@extends('layouts.master')

@section('main_content')
    @include('layouts.header', ['header' => $campaign['title'], 'subtitle' => ''])

    <div class="container -padded">
        <div class="wrapper" data-container="Signup" data-reviewing="true">
            Loading...
        </div>
    </div>
@stop
