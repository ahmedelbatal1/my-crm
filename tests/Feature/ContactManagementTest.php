<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class ContactManagementTest extends TestCase
{
    public function test_sales_rep_can_create_a_contact_with_a_company(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $company = Company::factory()->create();

        $response = $this->post('/contacts', [
            'name' => 'Jane Buyer',
            'phone' => '01000000000',
            'email' => 'jane@example.com',
            'company_id' => $company->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('contacts', [
            'name' => 'Jane Buyer',
            'phone' => '01000000000',
            'company_id' => $company->id,
            'user_id' => $salesRep->id,
        ]);
    }

    public function test_sales_rep_can_create_a_contact_without_a_company(): void
    {
        $salesRep = $this->actingAsSalesRep();

        $response = $this->post('/contacts', [
            'name' => 'John Individual',
            'phone' => '01011111111',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('contacts', [
            'name' => 'John Individual',
            'company_id' => null,
            'user_id' => $salesRep->id,
        ]);
    }

    public function test_creating_a_contact_requires_authentication(): void
    {
        $response = $this->post('/contacts', [
            'name' => 'Nobody',
            'phone' => '01000000000',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_contact_creation_ignores_a_submitted_user_id_for_a_sales_rep(): void
    {
        $salesRep = $this->actingAsSalesRep();
        $otherRep = User::factory()->salesRep()->create();

        $this->post('/contacts', [
            'name' => 'Jane Buyer',
            'phone' => '01000000000',
            'user_id' => $otherRep->id,
        ]);

        $this->assertDatabaseHas('contacts', [
            'name' => 'Jane Buyer',
            'user_id' => $salesRep->id,
        ]);
    }
}
