<?php

declare(strict_types=1);

namespace App\Enums;

enum BehavioralEventSeverity: string
{
    case Critical = 'critical';
    case Warning = 'warning';
    case Info = 'info';
}