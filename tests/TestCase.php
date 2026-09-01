<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(?User $user = null): User
    {
        $user ??= User::factory()->admin()->create();

        $this->actingAs($user);

        return $user;
    }

    protected function actingAsSalesRep(?User $user = null): User
    {
        $user ??= User::factory()->salesRep()->create();

        $this->actingAs($user);

        return $user;
    }
}
