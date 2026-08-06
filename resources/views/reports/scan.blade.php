<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Scan Report #{{ $scan->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            padding: 32px;
        }
        h1, h2 { margin-bottom: 0.5rem; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        th { background: #f3f4f6; }
        .muted { color: #6b7280; }
        .section { margin-top: 1.5rem; }
        .remediation { page-break-inside: avoid; margin-top: 1rem; }
        .remediation h3 { margin: 0 0 0.35rem; font-size: 15px; }
        .remediation p { margin: 0.3rem 0; }
        .remediation-label { font-weight: bold; color: #374151; }
        .code { white-space: pre-wrap; word-wrap: break-word; background: #f3f4f6; padding: 8px; }
    </style>
</head>
<body>
    <h1>Security Scan Report</h1>
    <p class="muted">Repository: {{ $scan->repository->owner }}/{{ $scan->repository->name }}</p>
    <p class="muted">Scan ID: {{ $scan->id }} | Status: {{ $scan->status->value ?? $scan->status }}</p>

    <div class="section">
        <h2>Executive Summary</h2>
        <table>
            <tr><th>Summary</th><td>{{ $aiSummary['executive_summary'] ?? 'N/A' }}</td></tr>
            <tr><th>Top Risks</th><td>{{ $aiSummary['top_risks'] ?? 'N/A' }}</td></tr>
            <tr><th>Remediation Focus</th><td>{{ $aiSummary['remediation_focus'] ?? 'N/A' }}</td></tr>
            <tr><th>Trend Commentary</th><td>{{ $aiSummary['trend_commentary'] ?? 'N/A' }}</td></tr>
            <tr><th>Next Steps</th><td>{{ $aiSummary['next_steps'] ?? 'N/A' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Scores</h2>
        <table>
            <tr><th>Security</th><td>{{ $scan->security_score }}</td></tr>
            <tr><th>Code Quality</th><td>{{ $scan->code_quality_score }}</td></tr>
            <tr><th>Dependency</th><td>{{ $scan->dependency_score }}</td></tr>
            <tr><th>Secret</th><td>{{ $scan->secret_score }}</td></tr>
            <tr><th>Overall Health</th><td>{{ $scan->overall_health_score }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Findings</h2>
        <table>
            <thead>
                <tr>
                    <th>Severity</th>
                    <th>Tool</th>
                    <th>Title</th>
                    <th>File</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($scan->findings as $finding)
                    <tr>
                        <td>{{ $finding->severity->value ?? $finding->severity }}</td>
                        <td>{{ $finding->tool }}</td>
                        <td>{{ $finding->title }}</td>
                        <td>{{ $finding->file_path }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Remediation</h2>
        @forelse ($scan->findings as $finding)
            <div class="remediation">
                <h3>{{ $finding->title }}</h3>
                <p class="muted">
                    {{ $finding->severity->value ?? $finding->severity }}
                    @if ($finding->file_path) · {{ $finding->file_path }} @endif
                    @if ($finding->line_number) · line {{ $finding->line_number }} @endif
                </p>
                @if ($finding->recommendation)
                    <p><span class="remediation-label">Summary:</span> {{ $finding->recommendation->plain_english_summary }}</p>
                    <p><span class="remediation-label">Business impact:</span> {{ $finding->recommendation->business_impact }}</p>
                    <p><span class="remediation-label">Technical explanation:</span> {{ $finding->recommendation->technical_explanation }}</p>
                    <p><span class="remediation-label">Recommended fix:</span> {{ $finding->recommendation->recommended_fix }}</p>
                    @if ($finding->recommendation->secure_code_example)
                        <p class="remediation-label">Secure code example:</p>
                        <div class="code">{{ $finding->recommendation->secure_code_example }}</div>
                    @endif
                @else
                    <p>{{ $finding->description ?: 'No remediation recommendation is available for this finding.' }}</p>
                @endif
            </div>
        @empty
            <p class="muted">No findings require remediation.</p>
        @endforelse
    </div>
</body>
</html>
