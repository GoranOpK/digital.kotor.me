<?php

namespace App\Models;

use App\Exceptions\CulturalActivityRecordException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Blokira kanonski query update/delete put za TS-012 zapise.
 */
class CulturalActivityRecordBuilder extends Builder
{
    public function update(array $values)
    {
        throw new CulturalActivityRecordException('Audit zapis je nepromjenjiv.');
    }

    public function delete()
    {
        throw new CulturalActivityRecordException('Audit zapis je nepromjenjiv.');
    }
}
