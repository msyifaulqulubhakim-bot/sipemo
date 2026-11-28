<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Notifikasi SIPeMo' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
            -webkit-font-smoothing: antialiased;
            line-height: 1.6;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f4f6f9;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            padding: 32px 30px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 13px;
            color: #d1fae5;
            font-weight: 400;
        }
        .body-content {
            padding: 32px 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 16px;
        }
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 12px 0;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .badge-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .badge-info {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .card-details {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px;
            margin: 20px 0;
        }
        .card-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .card-details td {
            padding: 6px 0;
            font-size: 14px;
            vertical-align: top;
        }
        .card-details td.label {
            width: 38%;
            color: #64748b;
            font-weight: 500;
        }
        .card-details td.value {
            width: 62%;
            color: #1e293b;
            font-weight: 600;
        }
        .notes-box {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 14px 18px;
            border-radius: 4px;
            margin: 18px 0;
            font-size: 14px;
            color: #92400e;
        }
        .notes-box-danger {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 14px 18px;
            border-radius: 4px;
            margin: 18px 0;
            font-size: 14px;
            color: #991b1b;
        }
        .notes-title {
            font-weight: 700;
            margin-bottom: 4px;
        }
        .button-wrapper {
            text-align: center;
            margin: 30px 0 10px 0;
        }
        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 8px rgba(5, 150, 105, 0.25);
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 30px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 4px 0;
        }
        .footer a {
            color: #059669;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <h1>SIPeMo</h1>
                <p>Sistem Informasi Penyusunan Modul Pembelajaran &bull; UPT PJJ UIN SSC</p>
            </div>

            <!-- Body -->
            <div class="body-content">
                @yield('content')
            </div>

            <!-- Footer -->
            <div class="footer">
                <p><strong>UPT Pembelajaran Jarak Jauh (PJJ)</strong></p>
                <p>UIN Siber Syekh Nurjati Cirebon</p>
                <p style="margin-top: 12px; font-size: 11px; color: #cbd5e1;">
                    Pemberitahuan otomatis dari sistem SIPeMo. Mohon tidak membalas email ini secara langsung.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
