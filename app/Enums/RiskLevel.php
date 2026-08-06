<?php

declare(strict_types=1);

namespace App\Enums;

enum RiskLevel: string
{
    case Low = 'Low Risk';
    case Medium = 'Medium Risk';
    case High = 'High Risk';
    case Critical = 'Critical Risk';
}
