<?php

namespace App\Observers;

use App\Enums\DealStage;
use App\Enums\UnitStatus;
use App\Models\Deal;

class DealObserver
{
    public function saved(Deal $deal): void
    {
        $this->syncUnitStatus($deal);
    }

    public function deleted(Deal $deal): void
    {
        $this->syncUnitStatus($deal);
    }

    private function syncUnitStatus(Deal $deal): void
    {
        $unit = $deal->unit()->first();

        if (! $unit) {
            return;
        }

        $stages = $unit->deals()->pluck('stage');

        $status = match (true) {
            $stages->contains(DealStage::ContractedWon) => UnitStatus::Sold,
            $stages->contains(DealStage::Reserved) => UnitStatus::Reserved,
            default => UnitStatus::Available,
        };

        if ($unit->status !== $status) {
            $unit->update(['status' => $status]);
        }
    }
}
