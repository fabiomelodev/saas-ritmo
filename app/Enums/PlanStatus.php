<?php

namespace App\Enums;

enum PlanStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
