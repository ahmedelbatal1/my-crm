<?php

namespace Tests\Feature;

use App\Enums\DealStage;
use App\Enums\UnitStatus;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Unit;
use Tests\TestCase;

class DealLifecycleTest extends TestCase
{
    public function test_sales_rep_can_open_a_deal_on_an_available_unit(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $contact = Contact::factory()->create(['user_id' => $salesRep->id]);
        $unit = Unit::factory()->create();

        $response = $this->post('/deals', [
            'contact_id' => $contact->id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('deals', [
            'contact_id' => $contact->id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
            'stage' => DealStage::Lead->value,
        ]);
    }

    public function test_sales_rep_can_advance_a_deal_to_reserved_with_a_deposit(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $contact = Contact::factory()->create(['user_id' => $salesRep->id]);
        $unit = Unit::factory()->create();
        $deal = Deal::factory()->create([
            'contact_id' => $contact->id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
            'stage' => DealStage::Lead,
        ]);

        $response = $this->put("/deals/{$deal->id}", [
            'contact_id' => $contact->id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
            'deposit_amount' => 450000,
            'deposit_paid_at' => '2026-08-26',
            'stage' => DealStage::Reserved->value,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'stage' => DealStage::Reserved->value,
            'deposit_amount' => 450000,
        ]);
        $this->assertSame(UnitStatus::Reserved, $unit->fresh()->status);
    }

    public function test_advancing_a_deal_to_contracted_won_marks_the_unit_sold(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $contact = Contact::factory()->create(['user_id' => $salesRep->id]);
        $unit = Unit::factory()->create();
        $deal = Deal::factory()->create([
            'contact_id' => $contact->id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
            'stage' => DealStage::Reserved,
        ]);

        $response = $this->put("/deals/{$deal->id}", [
            'contact_id' => $contact->id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
            'stage' => DealStage::ContractedWon->value,
        ]);

        $response->assertRedirect();

        $this->assertSame(DealStage::ContractedWon, $deal->fresh()->stage);
        $this->assertSame(UnitStatus::Sold, $unit->fresh()->status);
    }

    public function test_sales_reps_deal_index_is_grouped_by_stage(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $contact = Contact::factory()->create(['user_id' => $salesRep->id]);

        Deal::factory()->create(['contact_id' => $contact->id, 'stage' => DealStage::Lead]);
        Deal::factory()->create(['contact_id' => $contact->id, 'stage' => DealStage::Reserved]);
        Deal::factory()->create(['contact_id' => $contact->id, 'stage' => DealStage::ContractedWon]);

        $response = $this->get('/deals');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Deals/Index')
            ->has('dealsByStage.lead', 1)
            ->has('dealsByStage.reserved', 1)
            ->has('dealsByStage.contracted_won', 1)
        );
    }

    public function test_deal_creation_requires_full_price(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $contact = Contact::factory()->create(['user_id' => $salesRep->id]);
        $unit = Unit::factory()->create();

        $response = $this->post('/deals', [
            'contact_id' => $contact->id,
            'unit_id' => $unit->id,
            'full_price' => 0,
        ]);

        $response->assertSessionHasErrors('full_price');
    }

    public function test_deal_creation_rejects_a_deposit_greater_than_full_price(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $contact = Contact::factory()->create(['user_id' => $salesRep->id]);
        $unit = Unit::factory()->create();

        $response = $this->post('/deals', [
            'contact_id' => $contact->id,
            'unit_id' => $unit->id,
            'full_price' => 100000,
            'deposit_amount' => 200000,
        ]);

        $response->assertSessionHasErrors('deposit_amount');
    }
}
