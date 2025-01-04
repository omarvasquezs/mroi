@extends('template')

@section('content')
<div class="mb-4">
    <a href="{{ url('/') }}">← Regresar</a>
</div>
<pacientes-form></pacientes-form>
@endsection