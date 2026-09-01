<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Contact::class);

        $query = Contact::query()->with('company')->orderBy('name');

        if (! auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return Inertia::render('Contacts/Index', [
            'contacts' => $query->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Contact::class);

        return Inertia::render('Contacts/Form', [
            'companies' => Company::query()->orderBy('name')->get(),
            'salesReps' => auth()->user()->isAdmin()
                ? User::query()->where('role', 'sales_rep')->orderBy('name')->get()
                : [],
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $this->authorize('create', Contact::class);

        $contact = Contact::create($request->validated());

        return redirect("/contacts/{$contact->id}");
    }

    public function show(Contact $contact): Response
    {
        $this->authorize('view', $contact);

        return Inertia::render('Contacts/Show', [
            'contact' => $contact->load('company', 'deals.unit.project'),
        ]);
    }

    public function edit(Contact $contact): Response
    {
        $this->authorize('update', $contact);

        return Inertia::render('Contacts/Form', [
            'contact' => $contact,
            'companies' => Company::query()->orderBy('name')->get(),
            'salesReps' => auth()->user()->isAdmin()
                ? User::query()->where('role', 'sales_rep')->orderBy('name')->get()
                : [],
        ]);
    }

    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $contact->update($request->validated());

        return redirect("/contacts/{$contact->id}");
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);

        $contact->delete();

        return redirect('/contacts');
    }
}
