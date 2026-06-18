from __future__ import annotations

from dataclasses import dataclass
from datetime import datetime
from typing import Iterable

import pandas as pd


@dataclass(frozen=True)
class TrendPoint:
    created_at: datetime
    security_score: int
    code_quality_score: int
    dependency_score: int
    secret_score: int
    overall_health_score: int


def load_history(rows: Iterable[dict]) -> pd.DataFrame:
    frame = pd.DataFrame(list(rows))
    if frame.empty:
        return frame

    frame["created_at"] = pd.to_datetime(frame["created_at"])
    return frame.sort_values("created_at")


def trend_direction(history: pd.DataFrame) -> str:
    if history.empty or len(history) < 2:
        return "stable"

    latest = int(history.iloc[-1]["overall_health_score"])
    previous = int(history.iloc[0]["overall_health_score"])
    delta = latest - previous

    if delta >= 5:
        return "improving"
    if delta <= -5:
        return "worsening"
    return "stable"


def moving_average(history: pd.DataFrame, column: str, window: int = 3) -> list[float]:
    if history.empty or column not in history.columns:
        return []

    return (
        history[column]
        .rolling(window=window, min_periods=1)
        .mean()
        .round(2)
        .tolist()
    )


def analytics_payload(rows: Iterable[dict]) -> dict:
    history = load_history(rows)
    if history.empty:
        return {
            "trend_direction": "stable",
            "series": [],
            "moving_averages": {},
        }

    series = [
        {
            "label": row["created_at"].strftime("%Y-%m-%d"),
            "value": int(row["overall_health_score"]),
        }
        for _, row in history.iterrows()
    ]

    return {
        "trend_direction": trend_direction(history),
        "series": series,
        "moving_averages": {
            "security_score": moving_average(history, "security_score"),
            "overall_health_score": moving_average(history, "overall_health_score"),
        },
    }


if __name__ == "__main__":
    sample = [
        {
            "created_at": "2026-06-01T00:00:00",
            "security_score": 90,
            "code_quality_score": 88,
            "dependency_score": 92,
            "secret_score": 100,
            "overall_health_score": 91,
        },
        {
            "created_at": "2026-06-08T00:00:00",
            "security_score": 86,
            "code_quality_score": 88,
            "dependency_score": 90,
            "secret_score": 100,
            "overall_health_score": 89,
        },
    ]
    print(analytics_payload(sample))
