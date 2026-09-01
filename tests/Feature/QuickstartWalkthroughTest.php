<?php

namespace Tests\Feature;

use App\Enums\DealStage;
use App\Enums\UnitStatus;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Executes specs/002-real-estate-sales/quickstart.md's manual walkthrough (steps 1-5,
 * plus its "Expected end state") end-to-end over real HTTP requests, so the walkthrough
 * stays verifiable without a browser pass. Keep this in step with quickstart.md.
 */
class QuickstartWalkthroughTest extends TestCase
{
    public function test_quickstart_manual_walkthrough_end_to_end(): void
    {
        // Prerequisites: one admin, two sales reps.
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com', 'password' => Hash::make('password')]);
        $repA = User::factory()->salesRep()->create(['email' => 'repa@example.com', 'password' => Hash::make('password')]);
        $repB = User::factory()->salesRep()->create(['email' => 'repb@example.com', 'password' => Hash::make('password')]);

        // --- Step 1: set up inventory (as Admin) via a real login. ---
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($admin);

        $this->post('/projects', ['name' => 'Palm Hills Compound', 'location' => 'New Cairo'])->assertRedirect();
        $project = Project::where('name', 'Palm Hills Compound')->firstOrFail();

        $this->post("/projects/{$project->id}/units", ['type' => 'villa', 'area' => 350, 'price' => 4500000])->assertRedirect();
        $villa = Unit::where('project_id', $project->id)->firstOrFail();
        $this->assertSame(UnitStatus::Available, $villa->status, 'Step 1: new Unit must be available');

        $this->post('/logout')->assertRedirect();

        // --- Step 2 (part 1): Rep A creates a Contact and a Deal at lead. ---
        $this->post('/login', ['email' => 'repa@example.com', 'password' => 'password'])->assertRedirect();

        $this->post('/contacts', ['name' => 'Jane Buyer', 'phone' => '01000000000'])->assertRedirect();
        $jane = Contact::where('name', 'Jane Buyer')->firstOrFail();
        $this->assertSame($repA->id, $jane->user_id);

        $this->post('/deals', ['contact_id' => $jane->id, 'unit_id' => $villa->id, 'full_price' => 4500000])->assertRedirect();
        $dealA = Deal::where('contact_id', $jane->id)->firstOrFail();
        $this->assertSame(DealStage::Lead, $dealA->stage);
        $this->post('/logout');

        // --- Step 3 (part 1): Rep B opens a competing Deal on the same villa, before A wins. ---
        $this->post('/login', ['email' => 'repb@example.com', 'password' => 'password'])->assertRedirect();
        $this->post('/contacts', ['name' => 'Omar Buyer', 'phone' => '01011111111'])->assertRedirect();
        $omar = Contact::where('name', 'Omar Buyer')->firstOrFail();
        $this->post('/deals', ['contact_id' => $omar->id, 'unit_id' => $villa->id, 'full_price' => 4600000])->assertRedirect();
        $dealB = Deal::where('contact_id', $omar->id)->firstOrFail();
        $this->assertSame(2, $villa->deals()->count(), 'Step 3: both Deals coexist on one Unit');
        $this->post('/logout');

        // --- Step 2 (part 2): Rep A advances lead -> reserved (with deposit) -> contracted_won. ---
        $this->post('/login', ['email' => 'repa@example.com', 'password' => 'password'])->assertRedirect();

        $this->put("/deals/{$dealA->id}", [
            'contact_id' => $jane->id,
            'unit_id' => $villa->id,
            'full_price' => 4500000,
            'deposit_amount' => 450000,
            'deposit_paid_at' => '2026-09-01',
            'stage' => DealStage::Reserved->value,
        ])->assertRedirect();
        $this->assertSame(UnitStatus::Reserved, $villa->fresh()->status, 'Step 2: Unit flips to reserved');

        $this->put("/deals/{$dealA->id}", [
            'contact_id' => $jane->id,
            'unit_id' => $villa->id,
            'full_price' => 4500000,
            'deposit_amount' => 450000,
            'deposit_paid_at' => '2026-09-01',
            'stage' => DealStage::ContractedWon->value,
        ])->assertRedirect();
        $this->assertSame(UnitStatus::Sold, $villa->fresh()->status, 'Step 2: Unit flips to sold');

        // Step 3(a): a third new Deal on the sold villa is rejected.
        $this->post('/contacts', ['name' => 'Third Buyer', 'phone' => '01022222222'])->assertRedirect();
        $third = Contact::where('name', 'Third Buyer')->firstOrFail();
        $this->post('/deals', [
            'contact_id' => $third->id,
            'unit_id' => $villa->id,
            'full_price' => 4700000,
        ])->assertSessionHasErrors('unit_id');

        // Step 3(b): Rep B's Deal is untouched by A winning.
        $this->assertSame(DealStage::Lead, $dealB->fresh()->stage, 'Step 3: losing Deal is not auto-changed');

        // --- Step 4: isolation check from Rep A's side. ---
        $this->get('/contacts')->assertInertia(fn ($page) => $page->has('contacts', 2)); // Jane + Third, not Omar
        $this->get("/contacts/{$omar->id}")->assertForbidden();
        $this->get("/deals/{$dealB->id}")->assertForbidden();
        $this->post('/logout');

        // Step 3(c): Rep B manually closes their losing Deal as lost.
        $this->post('/login', ['email' => 'repb@example.com', 'password' => 'password'])->assertRedirect();
        $this->put("/deals/{$dealB->id}", [
            'contact_id' => $omar->id,
            'unit_id' => $villa->id,
            'full_price' => 4600000,
            'stage' => DealStage::Lost->value,
        ])->assertRedirect();
        $this->assertSame(DealStage::Lost, $dealB->fresh()->stage);

        // Isolation from Rep B's side.
        $this->get("/contacts/{$jane->id}")->assertForbidden();
        $this->get("/deals/{$dealA->id}")->assertForbidden();
        $this->post('/logout');

        // --- Step 5: Admin oversight + reassignment. ---
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password'])->assertRedirect();

        $this->get('/contacts')->assertInertia(fn ($page) => $page->has('contacts', 3));
        $this->get('/deals')->assertInertia(fn ($page) => $page
            ->has('dealsByStage.contracted_won', 1)
            ->has('dealsByStage.lost', 1)
        );

        $this->put("/contacts/{$jane->id}", [
            'name' => 'Jane Buyer',
            'phone' => '01000000000',
            'user_id' => $repB->id,
        ])->assertRedirect();
        $this->assertSame($repB->id, $jane->fresh()->user_id);
        $this->post('/logout');

        // Jane's Deal moved with her.
        $this->post('/login', ['email' => 'repb@example.com', 'password' => 'password']);
        $this->get("/deals/{$dealA->id}")->assertOk();
        $this->post('/logout');
        $this->post('/login', ['email' => 'repa@example.com', 'password' => 'password']);
        $this->get("/deals/{$dealA->id}")->assertForbidden();

        // --- Expected end state ---
        $this->assertSame(UnitStatus::Sold, $villa->fresh()->status);
        $this->assertSame(1, $villa->deals()->where('stage', DealStage::ContractedWon->value)->count());
        $this->assertSame(1, $villa->deals()->where('stage', DealStage::Lost->value)->count());
    }
}
