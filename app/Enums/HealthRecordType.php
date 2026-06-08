<?php

declare(strict_types=1);

namespace App\Enums;

enum HealthRecordType: string
{
    case Weight = 'weight';
    case Vaccination = 'vaccination';
    case Examination = 'examination';
    case LabWork = 'lab_work';
    case Dental = 'dental';
    case Surgery = 'surgery';
    case Medication = 'medication';
    case Other = 'other';
}