<?php

namespace App\Http\Controllers;

use App\Enums\DealStage;
use App\Http\Requests\DealRequest;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DealController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Deal::class);

        $query = Deal::query()->with('contact', 'unit.project');

        if (! auth()->user()->isAdmin()) {
            $query->whereHas('contact', fn ($q) => $q->where('user_id', auth()->id()));
        }

        $deals = $query->get();

        $dealsByStage = collect(DealStage::cases())
            ->mapWithKeys(fn (DealStage $stage) => [
                $stage->value => $deals->where('stage', $stage)->values(),
            ]);

        return Inertia::render('Deals/Index', [
            'dealsByStage' => $dealsByStage,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Deal::class);

        return Inertia::render('Deals/Form', [
            'contacts' => $this->selectableContacts(),
            'units' => $this->selectableUnits(),
            'preselectedContactId' => request()->integer('contact_id') ?: null,
            'preselectedUnitId' => request()->integer('unit_id') ?: null,
        ]);
    }

    public function store(DealRequest $request): RedirectResponse
    {
        $this->authorize('create', Deal::class);

        $deal = Deal::create($request->validated());

        return redirect("/deals/{$deal->id}");
    }

    public function show(Deal $deal): Response
    {
        $this->authorize('view', $deal);

        return Inertia::render('Deals/Show', [
            'deal' => $deal->load('contact', 'unit.project'),
        ]);
    }

    public function edit(Deal $deal): Response
    {
        $this->authorize('update', $deal);

        return Inertia::render('Deals/Form', [
            'deal' => $deal->load('contact', 'unit'),
            'contacts' => $this->selectableContacts(),
            'units' => $this->selectableUnits(),
        ]);
    }

    public function update(DealRequest $request, Deal $deal): RedirectResponse
    {
        $this->authorize('update', $deal);

        $deal->update($request->validated());

        return redirect("/deals/{$deal->id}");
    }

    public function destroy(Deal $deal): RedirectResponse
    {
        $this->authorize('delete', $deal);

        $deal->delete();

        return redirect('/deals');
    }

    private function selectableContacts(): Collection
    {
        $query = Contact::query()->orderBy('name');

        if (! auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return $query->get(['id', 'name']);
    }

    private function selectableUnits(): Collection
    {
        return Unit::query()
            ->with('project:id,name')
            ->orderBy('project_id')
            ->get(['id', 'project_id', 'type', 'price', 'status']);
    }
}
