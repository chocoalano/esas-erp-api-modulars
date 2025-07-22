@extends('layouts.layouts-pdf')

@section('title', 'Laporan')

@section('content')
    <table>
        <thead>
            <tr>
                @php
                    $header = [
                        'No.',
                        'Numbers',
                        'User',
                        'Type & Schedule',
                        'Time Adjust In/Out',
                        'Current & Adjust Shift',
                        'Date Range',
                        'Time Range',
                    ];
                @endphp
                @foreach ($header as $v)
                    <th>{{ $v }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $a)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $a->permit_numbers ?? '-' }}</td>
                    <td>{{ optional($a->user)->name ?? '-' }} | {{ optional($a->user)->nip ?? '-' }}</td>
                    <td>Type: {{ optional($a->permitType)->type ?? '-' }} | Schedule:
                        {{ optional($a->userTimeworkSchedule)->date ?? '-' }}</td>
                    <td>In: {{ $a->timein_adjust ?? '-' }} | Out: {{ $a->timeout_adjust ?? '-' }}</td>
                    <td>Current: {{ $a->current_shift_id ?? '-' }} | Adjust: {{ $a->adjust_shift_id ?? '-' }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($a->start_date)->format('Y-m-d') ?? '-' }}
                        to
                        {{ \Carbon\Carbon::parse($a->end_date)->format('Y-m-d') ?? '-' }}
                    </td>
                    <td>
                        {{ \Carbon\Carbon::parse($a->start_time)->format('H:i') ?? '-' }}
                        to
                        {{ \Carbon\Carbon::parse($a->end_time)->format('H:i') ?? '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
