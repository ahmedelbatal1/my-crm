<?php

namespace App\Http\Requests;

use App\Enums\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(UnitType::class)],
            'area' => ['required', 'numeric', 'min:0.01'],
            'price' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
