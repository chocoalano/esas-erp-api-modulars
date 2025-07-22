@extends('layouts.layouts-pdf')

@section('title', 'Laporan')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Type</th>
                <th>Is Pay</th>
                <th>Line Approved</th>
                <th>Manager Approved</th>
                <th>HR Approved</th>
                <th>File Required</th>
                <th>Show on mobile</th>
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
                    <td>{{ $a->type ?? '-' }}</td>
                    <td>{{ $a->is_pay ? 'Yes' : 'No' ?? '-' }}</td>
                    <td>{{ $a->approv_line ? 'Yes' : 'No' ?? '-' }}</td>
                    <td>{{ $a->approv_manager ? 'Yes' : 'No' ?? '-' }}</td>
                    <td>{{ $a->approv_hr ? 'Yes' : 'No' ?? '-' }}</td>
                    <td>{{ $a->with_file ? 'Yes' : 'No' ?? '-' }}</td>
                    <td>{{ $a->show_mobile ? 'Yes' : 'No' ?? '-' }}</td>
                    <td>{{ $a->created_at ?? '-' }}</td>
                    <td>{{ $a->updated_at ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
