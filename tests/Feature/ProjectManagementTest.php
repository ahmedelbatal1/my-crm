<?php

namespace Tests\Feature;

use App\Enums\DealStage;
use App\Enums\UnitStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Project;
use App\Models\Unit;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    public function test_any_authenticated_user_can_create_a_project(): void
    {
        $this->actingAsSalesRep();

        $response = $this->post('/projects', [
            'name' => 'Palm Hills Compound',
            'location' => 'New Cairo',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['name' => 'Palm Hills Compound']);
    }

    public function test_creating_a_unit_defaults_its_status_to_available(): void
    {
        $this->actingAsSalesRep();
        $project = Project::factory()->create();

        $response = $this->post("/projects/{$project->id}/units", [
            'type' => 'villa',
            'area' => 350,
            'price' => 4500000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('units', [
            'project_id' => $project->id,
            'type' => 'villa',
            'status' => UnitStatus::Available->value,
        ]);
    }

    public function test_deleting_a_project_with_units_is_blocked(): void
    {
        $this->actingAsSalesRep();
        $project = Project::factory()->create();
        Unit::factory()->create(['project_id' => $project->id]);

        $response = $this->delete("/projects/{$project->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_deleting_a_unit_with_deals_is_blocked(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $unit = Unit::factory()->create();
        Deal::factory()->create([
            'unit_id' => $unit->id,
            'stage' => DealStage::Lead,
        ]);

        $response = $this->delete("/units/{$unit->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }

    public function test_deleting_a_contact_with_deals_is_blocked(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $contact = Contact::factory()->create(['user_id' => $salesRep->id]);
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'stage' => DealStage::Lead,
        ]);

        $response = $this->delete("/contacts/{$contact->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    public function test_deleting_a_company_with_contacts_is_blocked(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $company = Company::factory()->create();
        Contact::factory()->create([
            'user_id' => $salesRep->id,
            'company_id' => $company->id,
        ]);

        $response = $this->delete("/companies/{$company->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    public function test_deletion_is_allowed_once_the_blocking_records_are_gone(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $company = Company::factory()->create();
        $contact = Contact::factory()->create([
            'user_id' => $salesRep->id,
            'company_id' => $company->id,
        ]);
        $unit = Unit::factory()->create();
        $project = $unit->project;
        $deal = Deal::factory()->create([
            'contact_id' => $contact->id,
            'unit_id' => $unit->id,
            'stage' => DealStage::Lead,
        ]);

        $this->delete("/deals/{$deal->id}")->assertRedirect();
        $this->delete("/contacts/{$contact->id}")->assertRedirect();
        $this->delete("/companies/{$company->id}")->assertRedirect();
        $this->delete("/units/{$unit->id}")->assertRedirect();
        $this->delete("/projects/{$project->id}")->assertRedirect();

        $this->assertDatabaseMissing('deals', ['id' => $deal->id]);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_any_authenticated_user_can_view_all_projects_and_units(): void
    {
        $this->actingAsSalesRep();
        $project = Project::factory()->create();
        Unit::factory()->count(2)->create(['project_id' => $project->id]);

        $response = $this->get("/projects/{$project->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('units', 2)
        );
    }
}
