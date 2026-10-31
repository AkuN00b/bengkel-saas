<?php

namespace App\Enums;

enum WorkshopRole: string
{
    case Owner = 'owner';
    case Cashier = 'cashier';
    case Mechanic = 'mechanic';
}
