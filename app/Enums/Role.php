<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Cashier = 'cashier';
    case DataEntry = 'data_entry';
}
