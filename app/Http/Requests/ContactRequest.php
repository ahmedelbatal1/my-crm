<?php

namespace App\Http\Requests;

use App\Models\Contact;
use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    /**
     * A Form Request authorizes before it validates, so the Policy check has to live
     * here too — otherwise a rep editing someone else's Contact would get a validation
     * error about the Contact's data instead of a 403 about the Contact itself.
     */
    public function authorize(): bool
    {
        $contact = $this->route('contact');

        return $contact
            ? $this->user()->can('update', $contact)
            : $this->user()->can('create', Contact::class);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->user()->isAdmin()) {
            $this->merge(['user_id' => $this->user()->id]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'user_id' => ['required', 'exists:users,id'],
        ];
    }
}
