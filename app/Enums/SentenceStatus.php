<?php

namespace App\Enums;

enum SentenceStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'pendente',
            self::Approved => 'aprovada',
            self::Rejected => 'rejeitada',
        };
    }
}
