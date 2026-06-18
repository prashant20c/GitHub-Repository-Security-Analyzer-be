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
    </style>
</head>
<body>
    <h1>Security Scan Report</h1>
    <p class="muted">Repository: {{ $scan->repository->owner }}/{{ $scan->repository->name }}</p>
    <p class="muted">Scan ID: {{ $scan->id }} | Status: {{ $scan->status->value ?? $scan->status }}</p>

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
</body>
</html>
