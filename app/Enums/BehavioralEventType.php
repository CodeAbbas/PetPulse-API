<?php

declare(strict_types=1);

namespace App\Enums;

enum BehavioralEventType: string
{
    case Pacing = 'pacing';
    case Presence = 'presence';
    case Vocalization = 'vocalization';
    case RapidZoneTransition = 'rapid_zone_transition';
    case ProlongedWaiting = 'prolonged_waiting';
}