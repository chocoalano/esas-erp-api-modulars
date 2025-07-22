<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi ESAS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 640px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color: #2E7D32;
            padding: 30px 40px;
            color: white;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            letter-spacing: 1px;
        }

        .content {
            padding: 30px 40px;
        }

        .content h2 {
            margin-top: 0;
            font-size: 22px;
            color: #2E7D32;
        }

        .content p {
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2E7D32;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }

        .footer {
            background-color: #e3eaf3;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            color: #666;
        }

        .footer a {
            color: #2E7D32;
            text-decoration: none;
        }

        @media only screen and (max-width: 600px) {
            .container, .content, .header, .footer {
                padding: 20px !important;
            }

            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ESAS</h1>
            <p style="margin-top: 10px; font-size: 14px;">Enterprise System & Automation Solution</p>
        </div>

        @yield('content')

        <div class="footer">
            Email ini dikirim secara otomatis oleh sistem ESAS.<br>
            Jika Anda merasa tidak terkait, silakan abaikan pesan ini.<br><br>
            &copy; {{ date('Y') }} <a href="{{ config('app.url') }}">{{ config('app.name') }}</a>. All rights reserved.
        </div>
    </div>
</body>
</html>
