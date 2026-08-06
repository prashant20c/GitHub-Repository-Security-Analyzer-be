# Python Analytics

This directory contains the trend-analysis and risk-classification helpers used by the Laravel backend.

## Responsibilities

- Convert scan history into chart-ready series
- Calculate trend direction and moving averages
- Classify repository risk using a lightweight decision-tree model

## Usage

The scripts accept JSON on `stdin` and emit JSON on `stdout` when invoked by Laravel:

```bash
echo '{"history":[{"created_at":"2026-07-01T00:00:00","overall_health_score":91}]}' | python analyze_trends.py
echo '{"metrics":{"overall_health_score":82}}' | python classify_risk.py
```

Standalone demo usage still works with no input:

```bash
pip install -r requirements.txt
python analyze_trends.py
python classify_risk.py
```
