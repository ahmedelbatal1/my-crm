<?php

namespace Tests\Feature;

use App\Enums\DealStage;
use App\Enums\UnitStatus;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Unit;
use App\Models\User;
use Tests\TestCase;

class CompetingDealsTest extends TestCase
{
    public function test_two_deals_from_different_contacts_can_coexist_on_the_same_unit(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $unit = Unit::factory()->create();
        $firstContact = Contact::factory()->create(['user_id' => $salesRep->id]);
        $secondContact = Contact::factory()->create(['user_id' => $salesRep->id]);

        $this->post('/deals', [
            'contact_id' => $firstContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
        ])->assertRedirect();

        $this->post('/deals', [
            'contact_id' => $secondContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4600000,
        ])->assertRedirect();

        $this->assertSame(2, $unit->deals()->count());
        $this->assertSame(UnitStatus::Available, $unit->fresh()->status);
    }

    public function test_two_competing_deals_may_both_reach_reserved_leaving_the_unit_reserved(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $unit = Unit::factory()->create();

        Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $salesRep->id])->id,
            'unit_id' => $unit->id,
            'stage' => DealStage::Reserved,
        ]);
        Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $salesRep->id])->id,
            'unit_id' => $unit->id,
            'stage' => DealStage::Reserved,
        ]);

        $this->assertSame(UnitStatus::Reserved, $unit->fresh()->status);
    }

    public function test_winning_deal_marks_the_unit_sold_and_leaves_the_competing_deal_untouched(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $unit = Unit::factory()->create();
        $winnerContact = Contact::factory()->create(['user_id' => $salesRep->id]);
        $loserContact = Contact::factory()->create(['user_id' => $salesRep->id]);

        $winner = Deal::factory()->create([
            'contact_id' => $winnerContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
            'stage' => DealStage::Reserved,
        ]);
        $loser = Deal::factory()->create([
            'contact_id' => $loserContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4600000,
            'stage' => DealStage::Reserved,
        ]);

        $this->put("/deals/{$winner->id}", [
            'contact_id' => $winnerContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
            'stage' => DealStage::ContractedWon->value,
        ])->assertRedirect();

        $this->assertSame(UnitStatus::Sold, $unit->fresh()->status);
        $this->assertSame(DealStage::Reserved, $loser->fresh()->stage);
    }

    public function test_creating_a_new_deal_on_a_sold_unit_is_rejected(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $unit = Unit::factory()->create();

        Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $salesRep->id])->id,
            'unit_id' => $unit->id,
            'stage' => DealStage::ContractedWon,
        ]);

        $newContact = Contact::factory()->create(['user_id' => $salesRep->id]);

        $response = $this->post('/deals', [
            'contact_id' => $newContact->id,
            'unit_id' => $unit->id,
            'full_price' => 5000000,
        ]);

        $response->assertSessionHasErrors('unit_id');
        $this->assertSame(1, $unit->deals()->count());
    }

    public function test_a_unit_already_flagged_sold_accepts_no_new_deals(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $unit = Unit::factory()->sold()->create();
        $contact = Contact::factory()->create(['user_id' => $salesRep->id]);

        $this->post('/deals', [
            'contact_id' => $contact->id,
            'unit_id' => $unit->id,
            'full_price' => 5000000,
        ])->assertSessionHasErrors('unit_id');

        $this->assertDatabaseCount('deals', 0);
    }

    public function test_lost_deals_alone_leave_a_unit_available(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $unit = Unit::factory()->create();

        Deal::factory()->lost()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $salesRep->id])->id,
            'unit_id' => $unit->id,
        ]);
        Deal::factory()->lost()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $salesRep->id])->id,
            'unit_id' => $unit->id,
        ]);

        $this->assertSame(UnitStatus::Available, $unit->fresh()->status);
    }

    public function test_a_contracted_won_deal_state_marks_its_unit_sold(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $unit = Unit::factory()->create();

        Deal::factory()->contractedWon()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $salesRep->id])->id,
            'unit_id' => $unit->id,
        ]);

        $this->assertSame(UnitStatus::Sold, $unit->fresh()->status);
    }

    public function test_a_reserved_deal_state_carries_its_deposit(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $unit = Unit::factory()->create();

        $deal = Deal::factory()->reserved()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $salesRep->id])->id,
            'unit_id' => $unit->id,
            'full_price' => 4000000,
        ]);

        $this->assertSame(UnitStatus::Reserved, $unit->fresh()->status);
        $this->assertNotNull($deal->deposit_amount);
        $this->assertNotNull($deal->deposit_paid_at);
    }

    public function test_editing_an_existing_deal_on_a_sold_unit_is_still_allowed(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $unit = Unit::factory()->create();
        $winnerContact = Contact::factory()->create(['user_id' => $salesRep->id]);
        $loserContact = Contact::factory()->create(['user_id' => $salesRep->id]);

        Deal::factory()->create([
            'contact_id' => $winnerContact->id,
            'unit_id' => $unit->id,
            'stage' => DealStage::ContractedWon,
        ]);
        $loser = Deal::factory()->create([
            'contact_id' => $loserContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4600000,
            'stage' => DealStage::Reserved,
        ]);

        $response = $this->put("/deals/{$loser->id}", [
            'contact_id' => $loserContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4600000,
            'stage' => DealStage::Lost->value,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame(DealStage::Lost, $loser->fresh()->stage);
        $this->assertSame(UnitStatus::Sold, $unit->fresh()->status);
    }

    public function test_owning_sales_rep_can_close_out_a_losing_deal_as_lost(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $unit = Unit::factory()->create();
        $loserContact = Contact::factory()->create(['user_id' => $salesRep->id]);

        Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $salesRep->id])->id,
            'unit_id' => $unit->id,
            'stage' => DealStage::ContractedWon,
        ]);
        $loser = Deal::factory()->create([
            'contact_id' => $loserContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4600000,
            'stage' => DealStage::Reserved,
        ]);

        $this->put("/deals/{$loser->id}", [
            'contact_id' => $loserContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4600000,
            'stage' => DealStage::Lost->value,
        ])->assertRedirect();

        $this->assertSame(DealStage::Lost, $loser->fresh()->stage);
    }

    public function test_admin_can_close_out_another_reps_losing_deal_as_lost(): void
    {
        $otherRep = User::factory()->salesRep()->create();
        $this->actingAsAdmin();

        $unit = Unit::factory()->create();
        $loserContact = Contact::factory()->create(['user_id' => $otherRep->id]);

        Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $otherRep->id])->id,
            'unit_id' => $unit->id,
            'stage' => DealStage::ContractedWon,
        ]);
        $loser = Deal::factory()->create([
            'contact_id' => $loserContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4600000,
            'stage' => DealStage::Reserved,
        ]);

        $this->put("/deals/{$loser->id}", [
            'contact_id' => $loserContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4600000,
            'stage' => DealStage::Lost->value,
        ])->assertRedirect();

        $this->assertSame(DealStage::Lost, $loser->fresh()->stage);
    }

    public function test_reversing_the_winning_deal_falls_back_to_the_other_deals_reserved_status(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $unit = Unit::factory()->create();
        $winnerContact = Contact::factory()->create(['user_id' => $salesRep->id]);

        $winner = Deal::factory()->create([
            'contact_id' => $winnerContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
            'stage' => DealStage::ContractedWon,
        ]);
        Deal::factory()->create([
            'contact_id' => Contact::factory()->create(['user_id' => $salesRep->id])->id,
            'unit_id' => $unit->id,
            'stage' => DealStage::Reserved,
        ]);

        $this->assertSame(UnitStatus::Sold, $unit->fresh()->status);

        $this->put("/deals/{$winner->id}", [
            'contact_id' => $winnerContact->id,
            'unit_id' => $unit->id,
            'full_price' => 4500000,
            'stage' => DealStage::Lost->value,
        ])->assertRedirect();

        $this->assertSame(UnitStatus::Reserved, $unit->fresh()->status);
    }
}
