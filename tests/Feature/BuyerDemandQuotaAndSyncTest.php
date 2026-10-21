<?php

namespace Tests\Feature;

use App\Models\BuyerDemand;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerDemandQuotaAndSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('adm_max_demands_per_user', 3);
    }

    public function test_closed_demands_do_not_count_against_submission_limit(): void
    {
        $user = User::factory()->create([
            'phone' => '01859032668',
            'email' => 'buyer@example.com',
            'role'  => 'buyer',
        ]);

        // Create 2 closed demands and 1 active demand for this user
        BuyerDemand::create([
            'user_id'          => $user->id,
            'buyer_name'       => $user->name,
            'buyer_phone'      => $user->phone,
            'buyer_email'      => $user->email,
            'property_type'    => 'land',
            'preferred_areas'  => ['Aftabnagar'],
            'status'           => 'closed',
            'requirement_code' => 'REQ-TEST1',
        ]);

        BuyerDemand::create([
            'user_id'          => $user->id,
            'buyer_name'       => $user->name,
            'buyer_phone'      => $user->phone,
            'buyer_email'      => $user->email,
            'property_type'    => 'flat',
            'preferred_areas'  => ['Aftabnagar'],
            'status'           => 'closed',
            'requirement_code' => 'REQ-TEST2',
        ]);

        BuyerDemand::create([
            'user_id'          => $user->id,
            'buyer_name'       => $user->name,
            'buyer_phone'      => $user->phone,
            'buyer_email'      => $user->email,
            'property_type'    => 'land',
            'preferred_areas'  => ['Aftabnagar'],
            'status'           => 'active',
            'requirement_code' => 'REQ-TEST3',
        ]);

        // There are 3 total records, but only 1 active demand.
        // The user should still be able to submit a new demand!
        $count = BuyerDemand::countDemandsForSubmitter($user->id, $user->phone, $user->email, onlyActive: true);
        $this->assertEquals(1, $count);

        $response = $this->actingAs($user)->post(route('chahida.store'), [
            'buyer_name'      => $user->name,
            'buyer_phone'     => $user->phone,
            'buyer_email'     => $user->email,
            'property_type'   => 'land',
            'preferred_areas' => ['Aftabnagar'],
            'notify_channels' => ['push', 'email'],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(2, BuyerDemand::where('status', 'active')->count());
    }

    public function test_buyer_dashboard_claims_and_displays_historical_demands(): void
    {
        $user = User::factory()->create([
            'phone' => '01859032668',
            'email' => 'buyer@example.com',
            'role'  => 'buyer',
        ]);

        // Create an unassigned guest demand matching user's phone with international prefix
        $guestDemand = BuyerDemand::create([
            'user_id'          => null,
            'buyer_name'       => 'Guest Buyer',
            'buyer_phone'      => '+8801859032668',
            'buyer_email'      => null,
            'property_type'    => 'land',
            'preferred_areas'  => ['Aftabnagar'],
            'status'           => 'active',
            'requirement_code' => 'REQ-GUEST1',
        ]);

        $this->assertNull($guestDemand->user_id);

        // Visiting buyer dashboard auto-claims the demand
        $response = $this->actingAs($user)->get(route('buyer.dashboard'));
        $response->assertOk();

        $guestDemand->refresh();
        $this->assertEquals($user->id, $guestDemand->user_id);
    }

    public function test_delete_demand_from_dashboard_actually_removes_from_database(): void
    {
        $user = User::factory()->create([
            'phone' => '01859032668',
            'email' => 'buyer@example.com',
            'role'  => 'buyer',
        ]);

        $demand = BuyerDemand::create([
            'user_id'          => $user->id,
            'buyer_name'       => $user->name,
            'buyer_phone'      => $user->phone,
            'buyer_email'      => $user->email,
            'property_type'    => 'land',
            'preferred_areas'  => ['Aftabnagar'],
            'status'           => 'active',
            'requirement_code' => 'REQ-DEL1',
        ]);

        $this->assertDatabaseHas('buyer_demands', ['id' => $demand->id]);

        $response = $this->actingAs($user)->postJson(route('buyer.dashboard.demand.delete'), [
            'demand_id' => $demand->id,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('buyer_demands', ['id' => $demand->id]);
    }

    public function test_agents_and_admins_can_bypass_strict_quota(): void
    {
        $agent = User::factory()->create([
            'phone' => '01859032668',
            'email' => 'agent@example.com',
            'role'  => 'agent',
        ]);

        // Create 3 active demands for this agent
        for ($i = 1; $i <= 3; $i++) {
            BuyerDemand::create([
                'user_id'          => $agent->id,
                'buyer_name'       => $agent->name,
                'buyer_phone'      => $agent->phone,
                'buyer_email'      => $agent->email,
                'property_type'    => 'land',
                'preferred_areas'  => ['Aftabnagar'],
                'status'           => 'active',
                'requirement_code' => "REQ-AGENT{$i}",
            ]);
        }

        // Agent submits 4th demand
        $response = $this->actingAs($agent)->post(route('chahida.store'), [
            'buyer_name'      => $agent->name,
            'buyer_phone'     => $agent->phone,
            'buyer_email'     => $agent->email,
            'property_type'   => 'land',
            'preferred_areas' => ['Aftabnagar'],
            'notify_channels' => ['push', 'email'],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(4, BuyerDemand::where('user_id', $agent->id)->count());
    }
}
