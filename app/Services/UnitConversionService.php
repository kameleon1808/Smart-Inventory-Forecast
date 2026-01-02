<?php

namespace App\Services;

use App\Domain\Inventory\Unit;
use App\Domain\Inventory\UnitConversion;
use InvalidArgumentException;

class UnitConversionService
{
    public function convert(float $amount, Unit $from, Unit $to): float
    {
        if ($from->id === $to->id) {
            return $amount;
        }

        $conversion = UnitConversion::where('from_unit_id', $from->id)
            ->where('to_unit_id', $to->id)
            ->first();

        if ($conversion) {
            return $amount * (float) $conversion->factor;
        }

        $inverse = UnitConversion::where('from_unit_id', $to->id)
            ->where('to_unit_id', $from->id)
            ->first();

        if ($inverse) {
            return $amount / (float) $inverse->factor;
        }

        throw new InvalidArgumentException('Conversion path not found between the provided units.');
    }
}
