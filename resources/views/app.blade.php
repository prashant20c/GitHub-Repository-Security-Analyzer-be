<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GitHub Repository Security Analyzer</title>
    <style>
        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0b1020;
            color: #eef2ff;
        }
        .shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px;
            background:
                radial-gradient(circle at top left, rgba(34, 197, 94, 0.18), transparent 30%),
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.20), transparent 35%),
                linear-gradient(160deg, #0b1020 0%, #111827 100%);
        }
        .card {
            max-width: 720px;
            background: rgba(15, 23, 42, 0.82);
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(18px);
        }
        h1 { margin-top: 0; font-size: clamp(2rem, 4vw, 3.25rem); }
        p { line-height: 1.6; color: #cbd5e1; }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 14px;
            background: rgba(34, 197, 94, 0.14);
            color: #86efac;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        code {
            background: rgba(148, 163, 184, 0.12);
            padding: 2px 6px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="card">
            <div class="pill">Backend API</div>
            <h1>GitHub Repository Security Analyzer</h1>
            <p>
                This backend exposes the authentication, repository management, scanning, analytics,
                and reporting APIs for the AI-powered security analyzer.
            </p>
            <p>
                Frontend code lives in the sibling <code>GitHub-Repository-Security-Analyzer-fe</code> repository.
            </p>
        </section>
    </main>
</body>
</html>
