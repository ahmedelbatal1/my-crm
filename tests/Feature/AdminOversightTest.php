<?php

namespace Tests\Feature;

use App\Enums\DealStage;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Tests\TestCase;

class AdminOversightTest extends TestCase
{
    public function test_admin_contact_list_includes_every_sales_reps_contacts(): void
    {
        $repA = User::factory()->salesRep()->create();
        $repB = User::factory()->salesRep()->create();

        Contact::factory()->create(['user_id' => $repA->id, 'name' => 'Rep A Buyer']);
        Contact::factory()->create(['user_id' => $repB->id, 'name' => 'Rep B Buyer']);

        $this->actingAsAdmin();

        $response = $this->get('/contacts');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Contacts/Index')
            ->has('contacts', 2)
        );
    }

    public function test_admin_deal_list_includes_every_sales_reps_deals(): void
    {
        $repA = User::factory()->salesRep()->create();
        $repB = User::factory()->salesRep()->create();

        Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $repA->id])->id,
            'stage' => DealStage::Lead,
        ]);
        Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $repB->id])->id,
            'stage' => DealStage::Reserved,
        ]);

        $this->actingAsAdmin();

        $response = $this->get('/deals');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Deals/Index')
            ->has('dealsByStage.lead', 1)
            ->has('dealsByStage.reserved', 1)
        );
    }

    public function test_admin_can_view_another_reps_contact_and_deal_directly(): void
    {
        $rep = User::factory()->salesRep()->create();
        $contact = Contact::factory()->create(['user_id' => $rep->id]);
        $deal = Deal::factory()->create(['contact_id' => $contact->id]);

        $this->actingAsAdmin();

        $this->get("/contacts/{$contact->id}")->assertOk();
        $this->get("/deals/{$deal->id}")->assertOk();
    }

    public function test_admin_can_reassign_a_contact_to_a_different_sales_rep(): void
    {
        $originalRep = User::factory()->salesRep()->create();
        $newRep = User::factory()->salesRep()->create();
        $contact = Contact::factory()->create([
            'user_id' => $originalRep->id,
            'name' => 'Reassigned Buyer',
            'phone' => '01022222222',
        ]);

        $this->actingAsAdmin();

        $response = $this->put("/contacts/{$contact->id}", [
            'name' => 'Reassigned Buyer',
            'phone' => '01022222222',
            'user_id' => $newRep->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'user_id' => $newRep->id,
        ]);
    }

    public function test_reassigning_a_contact_moves_its_deals_to_the_new_owner(): void
    {
        $originalRep = User::factory()->salesRep()->create();
        $newRep = User::factory()->salesRep()->create();
        $contact = Contact::factory()->create([
            'user_id' => $originalRep->id,
            'name' => 'Reassigned Buyer',
            'phone' => '01022222222',
        ]);
        $deal = Deal::factory()->create([
            'contact_id' => $contact->id,
            'stage' => DealStage::Reserved,
        ]);

        $this->actingAsAdmin();
        $this->put("/contacts/{$contact->id}", [
            'name' => 'Reassigned Buyer',
            'phone' => '01022222222',
            'user_id' => $newRep->id,
        ])->assertRedirect();

        // Deal ownership is derived through contact.user_id — never stored on deals.
        $this->actingAsSalesRep($newRep);
        $this->get("/deals/{$deal->id}")->assertOk();
        $this->get('/deals')->assertInertia(fn ($page) => $page
            ->component('Deals/Index')
            ->has('dealsByStage.reserved', 1)
        );

        $this->actingAsSalesRep($originalRep);
        $this->get("/deals/{$deal->id}")->assertForbidden();
        $this->get('/deals')->assertInertia(fn ($page) => $page
            ->component('Deals/Index')
            ->has('dealsByStage.reserved', 0)
        );
    }

    public function test_sales_rep_cannot_reassign_a_contact_to_another_rep(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $otherRep = User::factory()->salesRep()->create();
        $contact = Contact::factory()->create([
            'user_id' => $salesRep->id,
            'name' => 'Own Buyer',
            'phone' => '01033333333',
        ]);

        $this->put("/contacts/{$contact->id}", [
            'name' => 'Own Buyer',
            'phone' => '01033333333',
            'user_id' => $otherRep->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'user_id' => $salesRep->id,
        ]);
    }
}
