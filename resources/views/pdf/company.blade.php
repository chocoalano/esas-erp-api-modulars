@extends('layouts.layouts-pdf')

@section('title', 'Laporan')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Name</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Radius</th>
                <th>Address</th>
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
                    <td>{{ $a->latitude ?? '-' }}</td>
                    <td>{{ $a->longitude ?? '-' }}</td>
                    <td>{{ $a->radius ?? '-' }}</td>
                    <td>{{ $a->full_address ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
