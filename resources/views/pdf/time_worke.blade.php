@extends('layouts.layouts-pdf')

@section('title', 'Laporan')

@section('content')
    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>No.</th>
                <th>Company Name</th>
                <th>Dept Name</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Created At</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $a)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $a->company->name ?? '-' }}</td>
                    <td>{{ $a->departement->name ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($a->in)->format('H:i') ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($a->out)->format('H:i') ?? '-' }}</td>
                    <td>{{ $a->created_at }}</td>
                    <td>{{ $a->updated_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
