@extends('layouts.layouts-pdf')

@section('title', 'UserAttendance Report')

@section('content')
    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>No.</th>
                <th>User Id</th>
                <th>User Timework Schedule Id</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Type In</th>
                <th>Type Out</th>
                <th>Lat In</th>
                <th>Lat Out</th>
                <th>Long In</th>
                <th>Long Out</th>
                <th>Image In</th>
                <th>Image Out</th>
                <th>Status In</th>
                <th>Status Out</th>
                <th>Created By</th>
                <th>Updated By</th>
                <th>Created At</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $a)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $a->user_id ?? '-' }}</td>
                    <td>{{ $a->user_timework_schedule_id ?? '-' }}</td>
                    <td>{{ $a->time_in ?? '-' }}</td>
                    <td>{{ $a->time_out ?? '-' }}</td>
                    <td>{{ $a->type_in ?? '-' }}</td>
                    <td>{{ $a->type_out ?? '-' }}</td>
                    <td>{{ $a->lat_in ?? '-' }}</td>
                    <td>{{ $a->lat_out ?? '-' }}</td>
                    <td>{{ $a->long_in ?? '-' }}</td>
                    <td>{{ $a->long_out ?? '-' }}</td>
                    <td>{{ $a->image_in ?? '-' }}</td>
                    <td>{{ $a->image_out ?? '-' }}</td>
                    <td>{{ $a->status_in ?? '-' }}</td>
                    <td>{{ $a->status_out ?? '-' }}</td>
                    <td>{{ $a->created_by ?? '-' }}</td>
                    <td>{{ $a->updated_by ?? '-' }}</td>
                    <td>{{ $a->created_at ?? '-' }}</td>
                    <td>{{ $a->updated_at ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
