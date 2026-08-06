from __future__ import annotations

import json
import sys
from dataclasses import dataclass

from sklearn.tree import DecisionTreeClassifier


FEATURES = [
    "critical_count",
    "high_count",
    "medium_count",
    "low_count",
    "secret_leak_count",
    "dependency_risk_count",
    "security_score",
    "code_quality_score",
    "dependency_score",
    "secret_score",
    "overall_health_score",
]

LABELS = ["Low Risk", "Medium Risk", "High Risk", "Critical Risk"]


def train_model() -> DecisionTreeClassifier:
    samples = [
        [0, 0, 0, 1, 0, 0, 96, 94, 95, 100, 96],
        [0, 1, 1, 2, 0, 1, 84, 88, 86, 100, 87],
        [1, 2, 3, 4, 1, 2, 61, 72, 65, 85, 68],
        [3, 4, 5, 6, 3, 4, 25, 40, 35, 55, 31],
    ]
    target = LABELS

    model = DecisionTreeClassifier(max_depth=4, random_state=42)
    model.fit(samples, target)
    return model


def classify_risk(metrics: dict, model: DecisionTreeClassifier | None = None) -> str:
    model = model or train_model()
    row = [[metrics.get(feature, 0) for feature in FEATURES]]
    return str(model.predict(row)[0])


if __name__ == "__main__":
    raw = sys.stdin.read().strip()
    if raw:
        example = json.loads(raw).get("metrics", {})
    else:
        example = {
            "critical_count": 1,
            "high_count": 2,
            "medium_count": 1,
            "low_count": 0,
            "secret_leak_count": 1,
            "dependency_risk_count": 1,
            "security_score": 72,
            "code_quality_score": 78,
            "dependency_score": 75,
            "secret_score": 85,
            "overall_health_score": 74,
        }

    print(json.dumps({"risk_level": classify_risk(example)}))
