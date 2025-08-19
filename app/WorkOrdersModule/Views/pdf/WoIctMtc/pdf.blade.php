@extends('layouts.layouts-pdf')

@section('title', 'WoIctMtc Report')

@section('content')
    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>No.</th>
                <th>Wo No</th>
<th>Requested By Id</th>
<th>Request Date</th>
<th>Department Id</th>
<th>Area</th>
<th>Complaint</th>
<th>Asset Info</th>
<th>Status</th>
<th>Start Time</th>
<th>End Time</th>
<th>Created At</th>
<th>Updated At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $a)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $a->wo_no ?? '-' }}</td>
<td>{{ $a->requested_by_id ?? '-' }}</td>
<td>{{ $a->request_date ?? '-' }}</td>
<td>{{ $a->department_id ?? '-' }}</td>
<td>{{ $a->area ?? '-' }}</td>
<td>{{ $a->complaint ?? '-' }}</td>
<td>{{ $a->asset_info ?? '-' }}</td>
<td>{{ $a->status ?? '-' }}</td>
<td>{{ $a->start_time ?? '-' }}</td>
<td>{{ $a->end_time ?? '-' }}</td>
<td>{{ $a->created_at ?? '-' }}</td>
<td>{{ $a->updated_at ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
