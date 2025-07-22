<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Dokumen PDF')</title>
    <style>
        /* Base Styles */
        * {
            font-family: 'DejaVu Sans', sans-serif; /* Fallback for common PDF viewers */
            box-sizing: border-box;
        }

        body {
            margin: 30px; /* Ample margin for a cleaner look */
            color: #333; /* Softer black for text */
            background: #fff;
            line-height: 1.6; /* Improved readability */
        }

        /* Header (Kop Surat) */
        .header {
            border-bottom: 1px solid #eee; /* Lighter, more subtle border */
            padding-bottom: 15px; /* More space below header text */
            margin-bottom: 30px; /* Increased space before content */
            display: table; /* Use table-like display for better horizontal alignment in PDF */
            width: 100%;
        }

        .kop-container {
            display: table-row; /* For table-like layout */
        }

        .logo-wrapper { /* ⭐ NEW: Wrapper for the logo to apply border & centering ⭐ */
            width: 80px; /* Same width as logo */
            height: 80px; /* Make it square for better rounded border */
            border: 1px solid #ccc; /* Subtle border */
            border-radius: 8px; /* Rounded corners */
            overflow: hidden; /* Ensure image stays within bounds */
            display: table-cell; /* For table-like layout */
            vertical-align: middle;
            padding: 5px; /* Padding inside the border */
            text-align: center; /* Center the image horizontally if it's smaller */
        }

        .logo {
            max-width: 100%; /* Ensure image scales down to fit wrapper */
            max-height: 100%; /* Ensure image scales down to fit wrapper */
            display: block; /* Remove extra space below image */
            margin: 0 auto; /* Center the image if it's smaller than the wrapper */
        }

        .kop-text {
            text-align: left; /* Align text to left, more formal */
            display: table-cell;
            vertical-align: middle;
            line-height: 1.4; /* Tighter line height for KOP info */
        }

        .kop-text h1 {
            margin: 0;
            font-size: 16px; /* Slightly smaller, more refined */
            font-weight: bold;
            color: #2c3e50; /* A darker, professional tone */
            text-transform: uppercase;
        }

        .kop-text h2 {
            margin: 2px 0;
            font-size: 12px; /* Smaller for address */
            font-weight: normal; /* Less bold for address */
            color: #555;
        }

        .kop-text p {
            font-size: 10px; /* Smallest for contact info */
            margin: 0;
            color: #777;
        }

        /* Document Title */
        h2.title {
            text-align: center;
            margin-bottom: 25px; /* More space below title */
            font-size: 18px; /* Prominent title */
            font-weight: bold;
            color: #2c3e50;
            text-decoration: none; /* Remove underline for cleaner look */
            border-bottom: 1px solid #ccc; /* Subtle underline effect */
            padding-bottom: 5px;
            display: inline-block; /* To make border-bottom fit content width */
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px; /* More space above table */
        }

        th, td {
            border: 1px solid #ddd; /* Lighter border for tables */
            padding: 8px 12px; /* More padding for content */
            text-align: left;
            font-size: 11px; /* Consistent font size */
        }

        th {
            background-color: #f8f8f8; /* Light background for headers */
            font-weight: bold;
            color: #444;
            text-transform: uppercase; /* Professional touch */
        }

        /* Utility Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        /* Footer */
        .footer {
            margin-top: 40px; /* More space from content */
            text-align: right;
            font-size: 10px; /* Smaller footer text */
            color: #888; /* Muted color */
        }

        /* Signature Block (if used) */
        .signature {
            margin-top: 80px; /* Ample space for signature */
            text-align: right;
            font-size: 11px;
        }

        .signature p {
            margin-bottom: 5px;
        }

        .signature .name {
            font-weight: bold;
            border-bottom: 1px solid #333; /* Line for signature */
            display: inline-block; /* To make border-bottom fit content width */
            padding-bottom: 2px;
            margin-top: 20px; /* Space for actual signature */
        }

    </style>
</head>
<body>
    {{-- Kop Surat --}}
    <div class="header">
        <div class="kop-container">
            <div class="logo-wrapper">
                <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Company Logo">
            </div>
            <div style="margin-left: 10px"></div>
            <div class="kop-text">
                <h1>PT. Sinergi Abadi Sentosa</h1>
                <h2>Jl. Prabu Kian Santang No.169A, RT.001/RW.004, Sangiang Jaya, Kec. Periuk, Kota Tangerang, Banten 15132</h2>
                <p>Telepon: 0822-5807-7017 | Email: info@sinergiabadisentosa.com | Website: www.sinergiabadisentosa.com</p>
            </div>
        </div>
    </div>

    {{-- Konten Utama Dokumen --}}
    @yield('content')

    {{-- Footer --}}
    <div class="footer">
        <p>Dicetak pada: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM YYYY', 'Do MMMM YYYY', 'LL') }}</p>
    </div>
</body>
</html>
