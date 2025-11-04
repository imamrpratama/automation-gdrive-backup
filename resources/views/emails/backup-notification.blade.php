<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Drive Backup Report</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
        }

        .email-wrapper {
            padding: 40px 20px;
        }

        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 0;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .header {
            background-color: #111827;
            color: white;
            text-align: center;
            padding: 40px 30px;
            border-bottom: 3px solid #374151;
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-badge.warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .content {
            padding: 40px 30px;
        }

        .summary {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 30px;
            margin: 0 0 30px 0;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .summary-card {
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
        }

        .summary-icon {
            font-size: 28px;
            margin-bottom: 10px;
            opacity: 0.8;
        }

        .summary-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 24px;
            color: #111827;
            font-weight: 600;
        }

        .summary-card.success .summary-value {
            color: #059669;
        }

        .summary-card.warning .summary-value {
            color: #d97706;
        }

        .summary-card.danger .summary-value {
            color: #dc2626;
        }

        .summary-card.info .summary-value {
            color: #111827;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .info-value {
            color: #111827;
            font-weight: 500;
            font-size: 14px;
        }

        .failed-files {
            margin-top: 30px;
            padding: 25px;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 4px solid #dc2626;
            border-radius: 6px;
        }

        .failed-files h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #991b1b;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .failed-file {
            margin: 15px 0;
            padding: 15px;
            background-color: #ffffff;
            border: 1px solid #fee2e2;
            border-radius: 6px;
        }

        .failed-file-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            word-break: break-all;
            font-size: 14px;
        }

        .failed-file-error {
            color: #6b7280;
            font-size: 13px;
            padding: 8px 12px;
            background-color: #fef2f2;
            border-radius: 4px;
            border-left: 3px solid #dc2626;
        }

        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
            border-top: 1px solid #e5e7eb;
        }

        .footer-brand {
            font-weight: 600;
            color: #111827;
            margin-top: 10px;
            font-size: 14px;
        }

        @media only screen and (max-width: 600px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 20px;
            }

            .content {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="container">
            <div class="header">
                <h1>Google Drive Backup Report</h1>
                <div class="status-badge {{ $failCount > 0 ? 'warning' : 'success' }}">
                    {{ $failCount > 0 ? '⚠ ' : '✓ ' }}{{ strtoupper($status) }}
                </div>
            </div>

            <div class="content">
                <div class="summary">
                    <div class="summary-grid">
                        <div class="summary-card success">
                            <div class="summary-icon">✓</div>
                            <div class="summary-label">Successful</div>
                            <div class="summary-value">{{ $successCount }}</div>
                        </div>

                        @if ($skippedCount > 0)
                            <div class="summary-card warning">
                                <div class="summary-icon">⊘</div>
                                <div class="summary-label">Skipped</div>
                                <div class="summary-value">{{ $skippedCount }}</div>
                            </div>
                        @endif

                        @if ($failCount > 0)
                            <div class="summary-card danger">
                                <div class="summary-icon">✗</div>
                                <div class="summary-label">Failed</div>
                                <div class="summary-value">{{ $failCount }}</div>
                            </div>
                        @endif

                        <div class="summary-card info">
                            <div class="summary-icon">💾</div>
                            <div class="summary-label">Total Size</div>
                            <div class="summary-value" style="font-size: 18px;">{{ $totalSize }}</div>
                        </div>
                    </div>
                </div>

                <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 20px;">
                    <div class="info-row">
                        <span class="info-label">
                            <span style="margin-right: 4px;">⏱</span>
                            <span style="margin-right: 4px;">Duration</span>
                        </span>
                        <span class="info-value">{{ $duration }}s</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">
                            <span style="margin-right: 4px;">📅</span>
                            <span style="margin-right: 4px;">Timestamp</span>
                        </span>
                        <span class="info-value">{{ $timestamp }}</span>
                    </div>
                </div>

                @if (!empty($failedFiles))
                    <div class="failed-files">
                        <h3>
                            <span>⚠</span>
                            <span>Failed Files ({{ count($failedFiles) }})</span>
                        </h3>
                        @foreach ($failedFiles as $failed)
                            <div class="failed-file">
                                <div class="failed-file-name">📄 {{ $failed['file'] }}</div>
                                <div class="failed-file-error">{{ $failed['error'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="footer">
                <p style="margin: 0 0 10px 0;">Automated backup notification</p>
                <div class="footer-brand">{{ config('app.name', 'Laravel Application') }}</div>
            </div>
        </div>
    </div>
</body>

</html>
