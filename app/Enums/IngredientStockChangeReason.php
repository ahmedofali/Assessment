<?php

namespace App\Enums;

enum IngredientStockChangeReason: string
{
    case ORDER = 'order';
    case MANUAL = 'manual';
}
