<?php

namespace Tests\Feature;

use App\Enums\UnitStatus;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use Tests\TestCase;

class UnitManagementTest extends TestCase
{
    public function test_admin_can_view_units_created_by_a_sales_rep(): void
    {
        $salesRep = User::factory()->salesRep()->create();
        $this->actingAsSalesRep($salesRep);

        $project = Project::factory()->create();
        $this->post("/projects/{$project->id}/units", [
            'type' => 'apartment',
            'area' => 140,
            'price' => 2200000,
        ])->assertRedirect();

        $this->actingAsAdmin();

        $response = $this->get("/projects/{$project->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('units', 1)
        );
    }

    public function test_sales_rep_can_view_units_created_by_another_sales_rep(): void
    {
        $this->actingAsSalesRep();

        $project = Project::factory()->create();
        Unit::factory()->count(3)->create(['project_id' => $project->id]);

        $this->actingAsSalesRep();

        $response = $this->get("/projects/{$project->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('units', 3)
        );
    }

    public function test_project_index_lists_every_project_for_admin_and_sales_rep(): void
    {
        Project::factory()->count(2)->create();

        $this->actingAsAdmin();
        $this->get('/projects')->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->has('projects', 2)
        );

        $this->actingAsSalesRep();
        $this->get('/projects')->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->has('projects', 2)
        );
    }

    public function test_admin_can_add_a_unit_to_a_project(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->post("/projects/{$project->id}/units", [
            'type' => 'shop',
            'area' => 80,
            'price' => 1800000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('units', [
            'project_id' => $project->id,
            'type' => 'shop',
            'status' => UnitStatus::Available->value,
        ]);
    }

    public function test_unit_status_is_never_accepted_from_request_input(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->post("/projects/{$project->id}/units", [
            'type' => 'villa',
            'area' => 400,
            'price' => 6000000,
            'status' => UnitStatus::Sold->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('units', [
            'project_id' => $project->id,
            'status' => UnitStatus::Available->value,
        ]);
    }

    public function test_unit_creation_rejects_an_invalid_type(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->post("/projects/{$project->id}/units", [
            'type' => 'penthouse',
            'area' => 400,
            'price' => 6000000,
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_viewing_units_requires_authentication(): void
    {
        $project = Project::factory()->create();

        $this->get("/projects/{$project->id}")->assertRedirect('/login');
        $this->get('/projects')->assertRedirect('/login');
    }
}
