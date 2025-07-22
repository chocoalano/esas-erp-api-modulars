@extends('layouts.layouts-pdf')

@section('title', 'Hris Report')

@section('content')
    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>No.</th>
                <th>Company Id</th>
<th>Name</th>
<th>Nip</th>
<th>Email</th>
<th>Email Verified At</th>
<th>Password</th>
<th>Avatar</th>
<th>Status</th>
<th>Remember Token</th>
<th>Device Id</th>
<th>Created At</th>
<th>Updated At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $a)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $a->company_id ?? '-' }}</td>
<td>{{ $a->name ?? '-' }}</td>
<td>{{ $a->nip ?? '-' }}</td>
<td>{{ $a->email ?? '-' }}</td>
<td>{{ $a->email_verified_at ?? '-' }}</td>
<td>{{ $a->password ?? '-' }}</td>
<td>{{ $a->avatar ?? '-' }}</td>
<td>{{ $a->status ?? '-' }}</td>
<td>{{ $a->remember_token ?? '-' }}</td>
<td>{{ $a->device_id ?? '-' }}</td>
<td>{{ $a->created_at ?? '-' }}</td>
<td>{{ $a->updated_at ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
