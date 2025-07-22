@extends('layouts.layouts-pdf')

@section('title', 'Laporan')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Name</th>
                <th>GUard</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $a)
                <tr>
                    {{-- @if ($a->employee)
                    {{ dd($a->employee) }}
                    @endif --}}
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $a->name ?? '-' }}</td>
                    <td>{{ $a->guard_name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
