<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UserNameUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** A fixed UI context, never a user-supplied redirect URL. */
    public function returnsToProfile(): bool
    {
        return $this->input('name_context') === 'profile';
    }

    public function returnRoute(): string
    {
        return $this->returnsToProfile() ? 'profile.edit' : 'profile.name.edit';
    }

    protected function getRedirectUrl(): string
    {
        return route($this->returnRoute());
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ];
    }
}
