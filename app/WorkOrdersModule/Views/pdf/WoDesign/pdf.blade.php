@extends('layouts.layouts-pdf')

@section('title', 'WoDesign Report')

@section('content')
    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>No.</th>
                <th>Request No</th>
                <th>Request Date</th>
                <th>Need By Date</th>
                <th>Priority</th>
                <th>Pic Id</th>
                <th>Division Id</th>
                <th>Submitted To Id</th>
                <th>Acknowledged By Id</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Created At</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $a)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $a->request_no ?? '-' }}</td>
                    <td>{{ $a->request_date ?? '-' }}</td>
                    <td>{{ $a->need_by_date ?? '-' }}</td>
                    <td>{{ $a->priority ?? '-' }}</td>
                    <td>{{ $a->pic_id ?? '-' }}</td>
                    <td>{{ $a->division_id ?? '-' }}</td>
                    <td>{{ $a->submitted_to_id ?? '-' }}</td>
                    <td>{{ $a->acknowledged_by_id ?? '-' }}</td>
                    <td>{{ $a->status ?? '-' }}</td>
                    <td>{{ $a->notes ?? '-' }}</td>
                    <td>{{ $a->created_at ?? '-' }}</td>
                    <td>{{ $a->updated_at ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
