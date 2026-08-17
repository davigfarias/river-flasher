<?php

namespace App\Enums;

enum ActivityRange: string
{
    case Week = '7d';
    case Month = '30d';
    case All = 'all';
}
