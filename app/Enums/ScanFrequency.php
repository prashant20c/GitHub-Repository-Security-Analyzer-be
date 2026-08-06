<?php

declare(strict_types=1);

namespace App\Enums;

enum ScanFrequency: string
{
    case Manual = 'manual';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
}
