@extends('layouts.layouts-pdf')

@section('title', 'TimeUserSchedule Report')

@section('content')
    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>No.</th>
                <th>User Id</th>
                <th>Time Work Id</th>
                <th>Work Day</th>
                <th>Created At</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $a)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $a->user_id ?? '-' }}</td>
                    <td>{{ $a->time_work_id ?? '-' }}</td>
                    <td>{{ $a->work_day ?? '-' }}</td>
                    <td>{{ $a->created_at ?? '-' }}</td>
                    <td>{{ $a->updated_at ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
