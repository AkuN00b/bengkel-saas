<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
