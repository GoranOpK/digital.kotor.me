<?php

namespace App\Http\Requests\Concerns;

use App\Models\CulturalCategory;
use Illuminate\Validation\Validator;

trait ValidatesCulturalCatalogItem
{
    protected function prepareCatalogNazivAndOpis(): void
    {
        if ($this->has('naziv') && is_string($this->input('naziv'))) {
            $this->merge([
                'naziv' => trim($this->input('naziv')),
            ]);
        }

        if ($this->has('opis') && is_string($this->input('opis'))) {
            $opis = trim($this->input('opis'));
            $this->merge([
                'opis' => $opis === '' ? null : $opis,
            ]);
        }
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    protected function addActiveDuplicateValidation(
        Validator $validator,
        string $modelClass,
        string $routeParameter,
        string $activeStatus,
        string $entityLabel
    ): void {
        $validator->after(function (Validator $validator) use ($modelClass, $routeParameter, $activeStatus, $entityLabel) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $status = (string) $this->input('status');
            if ($status !== $activeStatus) {
                return;
            }

            $naziv = (string) $this->input('naziv');
            $exceptId = $this->route($routeParameter)?->getKey();

            if ($modelClass::activeDuplicateExists($naziv, $exceptId ? (int) $exceptId : null)) {
                $validator->errors()->add(
                    'naziv',
                    "Već postoji aktivna {$entityLabel} sa istim nazivom."
                );
            }
        });
    }

    protected function addForbiddenCategoryNameValidation(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('naziv')) {
                return;
            }

            $naziv = (string) $this->input('naziv');
            if (CulturalCategory::isForbiddenName($naziv)) {
                $validator->errors()->add(
                    'naziv',
                    'Kategorija „Nešto drugo“ nije dozvoljena. Proširite katalog novom kategorijom.'
                );
            }
        });
    }

    protected function authorizeKkAdmin(): bool
    {
        $user = $this->user();

        return $user
            && $user->role
            && $user->role->name === 'kk_admin';
    }
}
