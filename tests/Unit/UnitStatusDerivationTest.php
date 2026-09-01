<?php

namespace Tests\Unit;

use App\Enums\DealStage;
use App\Enums\UnitStatus;
use App\Models\Deal;
use App\Models\Unit;
use Tests\TestCase;

class UnitStatusDerivationTest extends TestCase
{
    public function test_unit_with_no_deals_is_available(): void
    {
        $unit = Unit::factory()->create();

        $this->assertSame(UnitStatus::Available, $unit->fresh()->status);
    }

    public function test_unit_with_a_lead_deal_stays_available(): void
    {
        $unit = Unit::factory()->create();

        Deal::factory()->for($unit)->create(['stage' => DealStage::Lead]);

        $this->assertSame(UnitStatus::Available, $unit->fresh()->status);
    }

    public function test_unit_with_a_reserved_deal_becomes_reserved(): void
    {
        $unit = Unit::factory()->create();

        Deal::factory()->for($unit)->create(['stage' => DealStage::Reserved]);

        $this->assertSame(UnitStatus::Reserved, $unit->fresh()->status);
    }

    public function test_unit_with_a_contracted_won_deal_becomes_sold(): void
    {
        $unit = Unit::factory()->create();

        Deal::factory()->for($unit)->create(['stage' => DealStage::ContractedWon]);

        $this->assertSame(UnitStatus::Sold, $unit->fresh()->status);
    }

    public function test_sold_takes_priority_over_reserved(): void
    {
        $unit = Unit::factory()->create();

        Deal::factory()->for($unit)->create(['stage' => DealStage::Reserved]);
        Deal::factory()->for($unit)->create(['stage' => DealStage::ContractedWon]);

        $this->assertSame(UnitStatus::Sold, $unit->fresh()->status);
    }

    public function test_reversing_the_winning_deal_falls_back_to_reserved_when_another_deal_is_reserved(): void
    {
        $unit = Unit::factory()->create();

        Deal::factory()->for($unit)->create(['stage' => DealStage::Reserved]);
        $winner = Deal::factory()->for($unit)->create(['stage' => DealStage::ContractedWon]);

        $winner->update(['stage' => DealStage::Lost]);

        $this->assertSame(UnitStatus::Reserved, $unit->fresh()->status);
    }

    public function test_reversing_the_only_winning_deal_falls_back_to_available(): void
    {
        $unit = Unit::factory()->create();

        $winner = Deal::factory()->for($unit)->create(['stage' => DealStage::ContractedWon]);

        $winner->update(['stage' => DealStage::Lost]);

        $this->assertSame(UnitStatus::Available, $unit->fresh()->status);
    }

    public function test_deleting_the_winning_deal_falls_back_to_available(): void
    {
        $unit = Unit::factory()->create();

        $winner = Deal::factory()->for($unit)->create(['stage' => DealStage::ContractedWon]);

        $winner->delete();

        $this->assertSame(UnitStatus::Available, $unit->fresh()->status);
    }
}
