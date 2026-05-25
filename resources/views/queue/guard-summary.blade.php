@extends('layouts.app')

@section('title', 'Queue Summary')

@section('content')
  @include('queue.guard-summary.index')
@endsection
