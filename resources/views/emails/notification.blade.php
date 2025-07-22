@extends('layouts.layouts-mail')
@section('content')
    <div class="content">
        <h2>Halo, {{ $data['name'] }}</h2>
        <p>{{ $data['message'] }}</p>

        @if (isset($data['action_url']))
            <p style="text-align: center;">
                <a href="{{ $data['action_url'] }}" class="button">
                    {{ $data['action_text'] ?? 'Lihat Detail' }}
                </a>
            </p>
        @endif
    </div>
@endsection
