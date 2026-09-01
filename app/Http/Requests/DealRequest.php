<?php

namespace App\Http\Requests;

use App\Enums\DealStage;
use App\Enums\UnitStatus;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DealRequest extends FormRequest
{
    /**
     * A Form Request authorizes before it validates, so the Policy check has to live
     * here too — otherwise a rep editing someone else's Deal would get a validation
     * error about the Deal's data instead of a 403 about the Deal itself.
     */
    public function authorize(): bool
    {
        $deal = $this->route('deal');

        return $deal
            ? $this->user()->can('update', $deal)
            : $this->user()->can('create', Deal::class);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('stage')) {
            $this->merge(['stage' => DealStage::Lead->value]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact_id' => [
                'required',
                'exists:contacts,id',
                function ($attribute, $value, $fail) {
                    if ($this->user()->isAdmin()) {
                        return;
                    }

                    $contact = Contact::find($value);

                    if ($contact && $contact->user_id !== $this->user()->id) {
                        $fail('You may only link deals to contacts you own.');
                    }
                },
            ],
            'unit_id' => [
                'required',
                'exists:units,id',
                function ($attribute, $value, $fail) {
                    // FR-010: only blocks *new* Deals; editing an existing Deal on a
                    // since-sold Unit (e.g. closing a losing Deal) stays allowed.
                    if ($this->route('deal')) {
                        return;
                    }

                    $unit = Unit::find($value);

                    if ($unit && $unit->status === UnitStatus::Sold) {
                        $fail('This unit is already sold; no new deals can be opened on it.');
                    }
                },
            ],
            'full_price' => ['required', 'numeric', 'min:0.01'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0', 'lte:full_price'],
            'deposit_paid_at' => ['nullable', 'date'],
            'stage' => ['required', Rule::enum(DealStage::class)],
        ];
    }
}
