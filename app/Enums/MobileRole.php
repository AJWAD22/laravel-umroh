<?php

namespace App\Enums;

enum MobileRole: string
{
    case Pilgrim = 'jamaah';
    case TourLeader = 'tour-leader';
    case Muthawwif = 'muthawwif';

    public function ability(): string
    {
        return "mobile:{$this->value}";
    }
}
