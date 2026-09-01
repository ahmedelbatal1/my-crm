<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Models\Project;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function create(Project $project): Response
    {
        $this->authorize('create', Unit::class);

        return Inertia::render('Units/Form', [
            'project' => $project,
        ]);
    }

    public function store(UnitRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', Unit::class);

        $project->units()->create($request->validated());

        return redirect("/projects/{$project->id}");
    }

    public function edit(Unit $unit): Response
    {
        $this->authorize('update', $unit);

        return Inertia::render('Units/Form', [
            'project' => $unit->project,
            'unit' => $unit,
        ]);
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->authorize('update', $unit);

        $unit->update($request->validated());

        return redirect("/projects/{$unit->project_id}");
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorize('delete', $unit);

        $projectId = $unit->project_id;

        $unit->delete();

        return redirect("/projects/{$projectId}");
    }
}
