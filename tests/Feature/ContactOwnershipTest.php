<?php

namespace Tests\Feature;

use App\Enums\DealStage;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Unit;
use App\Models\User;
use Tests\TestCase;

class ContactOwnershipTest extends TestCase
{
    private User $repA;

    private User $repB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repA = User::factory()->salesRep()->create();
        $this->repB = User::factory()->salesRep()->create();
    }

    public function test_sales_rep_contact_list_excludes_another_reps_contacts(): void
    {
        Contact::factory()->create(['user_id' => $this->repA->id, 'name' => 'Rep A Buyer']);
        Contact::factory()->create(['user_id' => $this->repB->id, 'name' => 'Rep B Buyer']);

        $this->actingAsSalesRep($this->repA);

        $response = $this->get('/contacts');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Contacts/Index')
            ->has('contacts', 1)
            ->where('contacts.0.name', 'Rep A Buyer')
        );
    }

    public function test_sales_rep_deal_list_excludes_another_reps_deals(): void
    {
        Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $this->repA->id])->id,
            'stage' => DealStage::Lead,
        ]);
        Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $this->repB->id])->id,
            'stage' => DealStage::Lead,
        ]);

        $this->actingAsSalesRep($this->repA);

        $response = $this->get('/deals');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Deals/Index')
            ->has('dealsByStage.lead', 1)
        );
    }

    public function test_sales_rep_is_denied_opening_another_reps_contact_by_direct_url(): void
    {
        $contact = Contact::factory()->create(['user_id' => $this->repB->id]);

        $this->actingAsSalesRep($this->repA);

        $this->get("/contacts/{$contact->id}")->assertForbidden();
        $this->get("/contacts/{$contact->id}/edit")->assertForbidden();
    }

    public function test_sales_rep_is_denied_opening_another_reps_deal_by_direct_url(): void
    {
        $deal = Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $this->repB->id])->id,
        ]);

        $this->actingAsSalesRep($this->repA);

        $this->get("/deals/{$deal->id}")->assertForbidden();
        $this->get("/deals/{$deal->id}/edit")->assertForbidden();
    }

    public function test_sales_rep_cannot_update_or_delete_another_reps_contact(): void
    {
        $contact = Contact::factory()->create([
            'user_id' => $this->repB->id,
            'name' => 'Rep B Buyer',
        ]);

        $this->actingAsSalesRep($this->repA);

        $this->put("/contacts/{$contact->id}", [
            'name' => 'Hijacked',
            'phone' => '01099999999',
        ])->assertForbidden();

        $this->delete("/contacts/{$contact->id}")->assertForbidden();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Rep B Buyer',
            'user_id' => $this->repB->id,
        ]);
    }

    public function test_denial_on_another_reps_record_wins_over_input_validation(): void
    {
        $contact = Contact::factory()->create(['user_id' => $this->repB->id]);
        $deal = Deal::factory()->create(['contact_id' => $contact->id]);

        $this->actingAsSalesRep($this->repA);

        // Invalid input must still be answered with 403, not a validation error that
        // would confirm anything about a record this rep may not touch.
        $this->put("/contacts/{$contact->id}", ['name' => ''])->assertForbidden();
        $this->put("/deals/{$deal->id}", ['full_price' => -1])->assertForbidden();
    }

    public function test_sales_rep_cannot_open_a_deal_against_another_reps_contact(): void
    {
        $contact = Contact::factory()->create(['user_id' => $this->repB->id]);
        $unit = Unit::factory()->create();

        $this->actingAsSalesRep($this->repA);

        $this->post('/deals', [
            'contact_id' => $contact->id,
            'unit_id' => $unit->id,
            'full_price' => 3000000,
        ])->assertSessionHasErrors('contact_id');

        $this->assertDatabaseCount('deals', 0);
    }

    public function test_deal_form_only_offers_the_acting_reps_own_contacts(): void
    {
        Contact::factory()->create(['user_id' => $this->repA->id, 'name' => 'Rep A Buyer']);
        Contact::factory()->create(['user_id' => $this->repB->id, 'name' => 'Rep B Buyer']);

        $this->actingAsSalesRep($this->repA);

        $response = $this->get('/deals/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Deals/Form')
            ->has('contacts', 1)
            ->where('contacts.0.name', 'Rep A Buyer')
        );
    }

    public function test_rep_cannot_close_out_a_competing_deal_owned_by_another_rep(): void
    {
        $unit = Unit::factory()->create();

        $dealA = Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $this->repA->id])->id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
            'stage' => DealStage::Reserved,
        ]);
        $contactB = Contact::factory()->create(['user_id' => $this->repB->id]);
        $dealB = Deal::factory()->create([
            'contact_id' => $contactB->id,
            'unit_id' => $unit->id,
            'full_price' => 4600000,
            'stage' => DealStage::Reserved,
        ]);

        $this->actingAsSalesRep($this->repA);

        // Rep A wins their own Deal — allowed.
        $this->put("/deals/{$dealA->id}", [
            'contact_id' => $dealA->contact_id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
            'stage' => DealStage::ContractedWon->value,
        ])->assertRedirect();

        // Rep A may not then close out Rep B's losing Deal on the same Unit.
        $this->put("/deals/{$dealB->id}", [
            'contact_id' => $contactB->id,
            'unit_id' => $unit->id,
            'full_price' => 4600000,
            'stage' => DealStage::Lost->value,
        ])->assertForbidden();

        $this->delete("/deals/{$dealB->id}")->assertForbidden();

        $this->assertSame(DealStage::Reserved, $dealB->fresh()->stage);
    }

    public function test_rep_cannot_see_a_competing_deal_on_a_shared_unit(): void
    {
        $unit = Unit::factory()->create();

        Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $this->repA->id])->id,
            'unit_id' => $unit->id,
            'stage' => DealStage::Lead,
        ]);
        Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $this->repB->id])->id,
            'unit_id' => $unit->id,
            'stage' => DealStage::Lead,
        ]);

        $this->actingAsSalesRep($this->repA);

        $this->get('/deals')->assertInertia(fn ($page) => $page
            ->component('Deals/Index')
            ->has('dealsByStage.lead', 1)
        );
    }
}
