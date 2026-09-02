<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Contract: specs/003-design-system-shell/contracts/shared-props.md §1.
 *
 * FR-009 (identity on every screen) and FR-039 (outcome messages) have no data source
 * without these shared props, which is why the spec pulls the shared-props middleware
 * into scope. No route, controller, policy or table is touched (FR-043).
 */
class SharedPropsTest extends TestCase
{
    public function test_a_sales_rep_sees_their_own_name_and_role(): void
    {
        $user = User::factory()->salesRep()->create(['name' => 'Mona Farouk']);

        $this->actingAs($user)
            ->get('/deals')
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.name', 'Mona Farouk')
                ->where('auth.user.role', 'sales_rep')
            );
    }

    public function test_an_admin_sees_their_admin_role(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Hala Adel']);

        $this->actingAs($user)
            ->get('/deals')
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.name', 'Hala Adel')
                ->where('auth.user.role', 'admin')
            );
    }

    public function test_a_guest_gets_a_null_user_rather_than_a_missing_key(): void
    {
        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->has('auth')
                ->where('auth.user', null)
            );
    }

    /**
     * Data & Security: only what the shell renders crosses the wire. The constitution
     * forbids exposing model attributes wholesale.
     */
    public function test_no_other_user_attribute_is_exposed(): void
    {
        $user = User::factory()->salesRep()->create();

        $response = $this->actingAs($user)->get('/deals');

        // A scoped closure without etc() fails if auth.user carries any key beyond
        // the two interacted with here — which is exactly the guarantee we want.
        $response->assertInertia(fn ($page) => $page
            ->has('auth.user', fn ($shared) => $shared
                ->has('name')
                ->has('role')
            )
        );

        $response->assertDontSee($user->email);
    }

    public function test_the_flash_region_always_receives_all_three_severities(): void
    {
        $this->actingAsSalesRep();

        $this->get('/deals')
            ->assertInertia(fn ($page) => $page
                ->has('flash')
                ->where('flash.success', null)
                ->where('flash.warning', null)
                ->where('flash.error', null)
            );
    }

    public function test_a_flashed_message_reaches_the_page(): void
    {
        $this->actingAsSalesRep();

        $this->withSession(['error' => 'Could not delete that record.'])
            ->get('/deals')
            ->assertInertia(fn ($page) => $page
                ->where('flash.error', 'Could not delete that record.')
            );
    }
}
