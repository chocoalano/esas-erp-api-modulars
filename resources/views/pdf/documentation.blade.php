@extends('layouts.layouts-pdf')

@section('title', 'Laporan')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Title</th>
                <th>Subtitle</th>
                <th>Created At</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $a)
                <tr>
                    {{-- @if ($a->employee)
                    {{ dd($a->employee) }}
                    @endif --}}
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $a->title ?? '-' }}</td>
                    <td>{{ $a->subtitle ?? '-' }}</td>
                    <td>{{ $a->created_at ?? '-' }}</td>
                    <td>{{ $a->updated_at ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
