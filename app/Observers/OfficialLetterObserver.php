<?php

namespace App\Observers;

use App\Models\OfficialLetter;
use App\Services\CodeGeneratorService;

class OfficialLetterObserver
{
    /**
     * Handle the OfficialLetter "creating" event.
     */
    public function creating(OfficialLetter $letter): void
    {
        if (empty($letter->letter_number)) {
            $letter->letter_number = CodeGeneratorService::generate(
                modelClass: OfficialLetter::class,
                column:     'letter_number',
                prefix:     'LTR',
                companyId:  $letter->company_id,
                padLength:  4,
            );
        }

        $this->normalizeEmptyAttributes($letter);
    }

    /**
     * Handle the OfficialLetter "updating" event.
     */
    public function updating(OfficialLetter $letter): void
    {
        $this->normalizeEmptyAttributes($letter);
    }

    /**
     * Convert empty string attributes to null.
     */
    protected function normalizeEmptyAttributes(OfficialLetter $letter): void
    {
        foreach ($letter->getAttributes() as $key => $value) {
            if ($value === '') {
                $letter->{$key} = null;
            }
        }
    }
}
