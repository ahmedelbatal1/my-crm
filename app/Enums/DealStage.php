<?php

namespace App\Enums;

enum DealStage: string
{
    case Lead = 'lead';
    case Reserved = 'reserved';
    case ContractedWon = 'contracted_won';
    case Lost = 'lost';
}
