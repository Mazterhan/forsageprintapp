<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Department;
use App\Models\OrderProposal;
use App\Models\OrderProposalEditLock;
use App\Models\PriceItem;
use App\Models\PriceItemHistory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesRoles;
use Tests\TestCase;

class PublicUrlRegressionTest extends TestCase
{
    use CreatesRoles;
    use RefreshDatabase;

    public function test_order_proposal_show_uses_public_id_binding_and_respects_own_scope(): void
    {
        $owner = $this->createUserWithRole([
            'can_orders' => true,
            'orders_proposals' => true,
            'orders_list_scope' => 'own',
        ]);
        $other = User::factory()->create([
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $ownProposal = OrderProposal::factory()->forUser($owner)->create();
        $otherProposal = OrderProposal::factory()->forUser($other)->create();

        $this->actingAs($owner)
            ->get(route('orders.proposals.show', $ownProposal))
            ->assertOk();

        $this->assertStringContainsString((string) $ownProposal->public_id, route('orders.proposals.show', $ownProposal));
        $this->assertNotSame(url('/orders/proposals/'.$ownProposal->id), route('orders.proposals.show', $ownProposal));

        $this->actingAs($owner)
            ->get('/orders/proposals/'.$ownProposal->id)
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('orders.proposals.show', $otherProposal))
            ->assertForbidden();
    }

    public function test_order_proposal_autosave_actions_and_edit_lock_keep_current_behavior(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_proposals' => true,
            'orders_edit' => true,
        ]);

        $autosavedToConfirm = OrderProposal::factory()
            ->forUser($user)
            ->create([
                'is_autosaved' => true,
                'autosaved_by' => $user->id,
                'autosaved_at' => now(),
                'autosave_token' => 'confirm-token',
            ]);
        $autosavedToDelete = OrderProposal::factory()
            ->forUser($user)
            ->create([
                'is_autosaved' => true,
                'autosaved_by' => $user->id,
                'autosaved_at' => now(),
                'autosave_token' => 'delete-token',
            ]);
        $proposalToEdit = OrderProposal::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->post('/orders/proposals/'.$autosavedToConfirm->id.'/confirm-autosave')
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('orders.proposals.confirm-autosave', $autosavedToConfirm))
            ->assertRedirect(route('orders.proposals.show', $autosavedToConfirm));

        $this->assertDatabaseHas('order_proposals', [
            'id' => $autosavedToConfirm->id,
            'is_autosaved' => false,
            'autosave_token' => null,
            'autosave_confirmed_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post('/orders/proposals/'.$autosavedToDelete->id.'/delete-autosave')
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('orders.proposals.delete-autosave', $autosavedToDelete))
            ->assertRedirect(route('orders.proposals'));

        $this->assertDatabaseHas('order_proposals', [
            'id' => $autosavedToDelete->id,
            'deleted_by' => $user->id,
        ]);
        $this->assertNotNull($autosavedToDelete->refresh()->deleted_date);

        $this->actingAs($user)
            ->postJson('/orders/proposals/'.$proposalToEdit->id.'/edit-lock')
            ->assertNotFound();

        $editResponse = $this->actingAs($user)
            ->postJson(route('orders.proposals.edit-lock.start', $proposalToEdit))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['ok', 'edit_url']);

        $this->assertStringContainsString((string) $proposalToEdit->public_id, (string) $editResponse->json('edit_url'));
        $this->assertStringNotContainsString('proposal='.$proposalToEdit->id, (string) $editResponse->json('edit_url'));

        $this->assertDatabaseHas('order_proposal_edit_locks', [
            'order_proposal_id' => $proposalToEdit->id,
            'user_id' => $user->id,
        ]);

        $lock = $proposalToEdit->editLock()->firstOrFail();

        $this->actingAs($user)
            ->patchJson(route('orders.proposals.edit-lock.heartbeat', $proposalToEdit), [
                'lock_token' => $lock->lock_token,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($user)
            ->deleteJson(route('orders.proposals.edit-lock.release', $proposalToEdit), [
                'lock_token' => $lock->lock_token,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('order_proposal_edit_locks', [
            'id' => $lock->id,
        ]);
    }

    public function test_order_calculation_accepts_public_proposal_id_with_valid_edit_token_and_rejects_numeric_id(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_calculation' => true,
            'orders_proposals' => true,
            'orders_edit' => true,
        ]);
        $proposal = OrderProposal::factory()->forUser($user)->create();
        $token = 'public-edit-token';

        OrderProposalEditLock::query()->create([
            'order_proposal_id' => $proposal->id,
            'user_id' => $user->id,
            'lock_token' => $token,
            'started_at' => now(),
            'heartbeat_at' => now(),
        ]);

        $sessionKey = "order_proposal_edit_tokens.{$proposal->id}";

        $this->withSession([$sessionKey => $token])
            ->actingAs($user)
            ->get(route('orders.calculation', [
                'proposal' => $proposal->public_id,
                'edit_token' => $token,
            ]))
            ->assertOk();

        $this->withSession([$sessionKey => $token])
            ->actingAs($user)
            ->get(route('orders.calculation', [
                'proposal' => $proposal->id,
                'edit_token' => $token,
            ]))
            ->assertNotFound();
    }

    public function test_client_routes_use_public_id_and_keep_current_crud_behavior(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create(['name' => 'Original Client']);
        $proposal = OrderProposal::factory()->create([
            'client_name' => 'Original Client',
            'payload' => array_replace_recursive(OrderProposal::factory()->payload(500), [
                'client_id' => $client->id,
                'client_name' => 'Original Client',
            ]),
        ]);

        $this->assertStringContainsString((string) $client->public_id, route('orders.clients.edit', $client));
        $this->assertStringNotContainsString('/'.$client->id.'/edit', route('orders.clients.edit', $client));

        $this->actingAs($user)
            ->get(route('orders.clients.edit', $client))
            ->assertOk();

        $this->actingAs($user)
            ->get('/orders/clients/'.$client->id.'/edit')
            ->assertNotFound();

        $this->actingAs($user)
            ->patch('/orders/clients/'.$client->id, [
                'name' => 'Numeric Updated Client',
                'type' => 'company',
                'status' => 'active',
                'is_vip' => '0',
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->patch(route('orders.clients.update', $client), [
                'name' => 'Updated Client',
                'type' => 'company',
                'status' => 'active',
                'is_vip' => '0',
            ])
            ->assertRedirect(route('orders.clients.edit', $client));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Client',
        ]);
        $this->assertDatabaseHas('order_proposals', [
            'id' => $proposal->id,
            'client_name' => 'Updated Client',
        ]);
        $this->assertSame('Updated Client', $proposal->refresh()->payload['client_name']);

        $this->actingAs($user)
            ->patch('/orders/clients/'.$client->id.'/deactivate')
            ->assertNotFound();

        $this->actingAs($user)
            ->patch(route('orders.clients.deactivate', $client))
            ->assertRedirect(route('orders.clients.index'));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => 'blocked',
        ]);
    }

    public function test_order_calculation_save_still_creates_client_from_manual_client_name(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_calculation' => true,
            'orders_calc_save' => true,
            'orders_proposals' => true,
        ]);
        $payload = OrderProposal::factory()->payload(700);
        $payload['client_id'] = null;
        $payload['client_name'] = 'Manual Calculator Client';

        $this->actingAs($user)
            ->postJson(route('orders.proposals.store'), [
                'state' => $payload,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('clients', [
            'name' => 'Manual Calculator Client',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        $this->assertDatabaseHas('order_proposals', [
            'client_name' => 'Manual Calculator Client',
            'user_id' => $user->id,
        ]);
    }

    public function test_price_routes_use_public_id_and_keep_current_mutation_behavior(): void
    {
        $user = $this->createUserWithRole([
            'can_price' => true,
            'price_card_access' => true,
            'price_card_edit' => true,
            'price_deactivate_item' => true,
            'price_delete_item' => true,
            'price_card_history' => true,
        ]);
        $item = PriceItem::factory()->create([
            'internal_code' => 'MAT-TST-901',
            'service_price' => 100,
            'purchase_price' => 50,
        ]);
        $history = PriceItemHistory::factory()->create([
            'price_item_id' => $item->id,
            'service_price' => 80,
            'purchase_price' => 40,
            'user_id' => $user->id,
        ]);
        $otherItem = PriceItem::factory()->create([
            'internal_code' => 'MAT-TST-902',
            'service_price' => 300,
            'purchase_price' => 150,
        ]);
        $otherHistory = PriceItemHistory::factory()->create([
            'price_item_id' => $otherItem->id,
            'service_price' => 200,
            'purchase_price' => 100,
            'user_id' => $user->id,
        ]);

        $this->assertStringContainsString((string) $item->public_id, route('price.show', $item));
        $this->assertStringNotContainsString($item->internal_code, route('price.show', $item));

        $this->actingAs($user)
            ->get(route('price.show', $item))
            ->assertOk();

        $this->actingAs($user)
            ->get('/price/'.$item->internal_code)
            ->assertNotFound();

        $this->actingAs($user)
            ->get('/price/'.$item->id)
            ->assertNotFound();

        $this->actingAs($user)
            ->patch(route('price.update', $item), [
                'service_price' => 120,
                'purchase_price' => 60,
                'comment' => 'Regression note',
            ])
            ->assertRedirect(route('price.show', $item));

        $this->assertDatabaseHas('price_items', [
            'id' => $item->id,
            'service_price' => 120,
            'purchase_price' => 60,
            'comment' => 'Regression note',
        ]);

        $this->actingAs($user)
            ->patch('/price/'.$item->internal_code, [
                'service_price' => 125,
                'purchase_price' => 65,
                'comment' => 'Internal code URL should not update',
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->patch(route('price.toggle', $item))
            ->assertRedirect(route('price.index'));

        $this->assertDatabaseHas('price_items', [
            'id' => $item->id,
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->patch('/price/'.$item->id.'/toggle')
            ->assertNotFound();

        $this->assertStringContainsString((string) $history->public_id, route('price.history.revert', [$item, $history]));
        $this->assertStringNotContainsString('/history/'.$history->id.'/revert', route('price.history.revert', [$item, $history]));

        $this->actingAs($user)
            ->post(route('price.history.revert', [$item, $otherHistory]))
            ->assertNotFound();

        $this->actingAs($user)
            ->post('/price/'.$item->public_id.'/history/'.$history->id.'/revert')
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('price.history.revert', [$item, $history]))
            ->assertRedirect(route('price.show', $item));

        $this->assertDatabaseHas('price_items', [
            'id' => $item->id,
            'service_price' => 80,
            'purchase_price' => 40,
        ]);

        $this->actingAs($user)
            ->patch(route('price.hide', $item))
            ->assertRedirect(route('price.index'));

        $this->actingAs($user)
            ->patch('/price/'.$item->id.'/hide')
            ->assertNotFound();

        $this->assertDatabaseHas('price_items', [
            'id' => $item->id,
            'visible' => false,
        ]);
    }

    public function test_price_public_id_routes_still_require_price_permissions(): void
    {
        $userWithoutPrice = $this->createUserWithRole([
            'can_price' => false,
            'price_card_access' => false,
            'price_card_edit' => false,
            'price_deactivate_item' => false,
            'price_delete_item' => false,
            'price_card_history' => false,
        ]);
        $item = PriceItem::factory()->create();
        $history = PriceItemHistory::factory()->create([
            'price_item_id' => $item->id,
            'user_id' => $userWithoutPrice->id,
        ]);

        $this->actingAs($userWithoutPrice)
            ->get(route('price.show', $item))
            ->assertForbidden();

        $this->actingAs($userWithoutPrice)
            ->patch(route('price.update', $item), [
                'service_price' => 10,
                'purchase_price' => 5,
            ])
            ->assertForbidden();

        $this->actingAs($userWithoutPrice)
            ->patch(route('price.toggle', $item))
            ->assertForbidden();

        $this->actingAs($userWithoutPrice)
            ->patch(route('price.hide', $item))
            ->assertForbidden();

        $this->actingAs($userWithoutPrice)
            ->post(route('price.history.revert', [$item, $history]))
            ->assertForbidden();
    }

    public function test_admin_user_routes_use_public_id_and_keep_current_behavior(): void
    {
        $admin = $this->createAdminUser();
        $managedUser = User::factory()->create([
            'is_active' => true,
            'force_password_change' => false,
        ]);
        $role = Role::factory()->create(['name' => 'Public Admin Role', 'slug' => 'public-admin-role']);
        $department = Department::factory()->create(['name' => 'Public Admin Department']);

        $this->assertStringContainsString((string) $managedUser->public_id, route('admin.users.edit', $managedUser));
        $this->assertStringNotContainsString('/'.$managedUser->id.'/edit', route('admin.users.edit', $managedUser));

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $managedUser))
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/users/'.$managedUser->id.'/edit')
            ->assertNotFound();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $managedUser), [
                'name' => 'Updated Managed User',
                'email' => 'updated-managed-user@example.test',
                'role' => $role->slug,
                'is_active' => '1',
                'department_id' => $department->id,
                'department_category_id' => null,
                'department_position_id' => null,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'name' => 'Updated Managed User',
            'email' => 'updated-managed-user@example.test',
            'role' => $role->slug,
            'department_id' => $department->id,
        ]);

        $this->actingAs($admin)
            ->patch('/admin/users/'.$managedUser->id, [
                'name' => 'Numeric Managed User',
                'email' => 'numeric-managed-user@example.test',
                'role' => $role->slug,
                'is_active' => '1',
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->patch(route('admin.users.reset-password', $managedUser), [
                'reset_reason' => 'Regression reset',
                'password' => 'temporary',
                'password_confirmation' => 'temporary',
            ])
            ->assertRedirect(route('admin.users.edit', $managedUser));

        $this->assertDatabaseHas('user_password_reset_logs', [
            'user_id' => $managedUser->id,
            'reset_by_user_id' => $admin->id,
            'reason' => 'Regression reset',
        ]);
        $this->assertTrue($managedUser->refresh()->force_password_change);

        $this->actingAs($admin)
            ->patch('/admin/users/'.$managedUser->id.'/reset-password', [
                'reset_reason' => 'Numeric reset',
                'password' => 'temporary',
                'password_confirmation' => 'temporary',
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle', $managedUser))
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($managedUser->refresh()->is_active);

        $this->actingAs($admin)
            ->patch('/admin/users/'.$managedUser->id.'/toggle-active')
            ->assertNotFound();
    }

    public function test_admin_role_routes_use_public_id_and_keep_current_behavior(): void
    {
        $admin = $this->createAdminUser();
        $role = Role::factory()->create([
            'name' => 'Editable Role',
            'slug' => 'editable-role',
            'can_orders' => true,
            'orders_proposals' => true,
            'orders_list_scope' => 'all',
        ]);

        $this->assertStringContainsString((string) $role->public_id, route('admin.roles.edit', $role));
        $this->assertStringNotContainsString('/'.$role->id.'/edit', route('admin.roles.edit', $role));

        $this->actingAs($admin)
            ->get(route('admin.roles.edit', $role))
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/roles/'.$role->id.'/edit')
            ->assertNotFound();

        $payload = $this->rolePayload([
            'name' => 'Updated Editable Role',
            'can_orders' => '1',
            'orders_proposals' => '1',
            'orders_list_scope' => 'all',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.roles.update', $role), $payload)
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Updated Editable Role',
            'can_orders' => true,
            'orders_proposals' => true,
            'orders_list_scope' => 'all',
        ]);

        $this->actingAs($admin)
            ->patch('/admin/roles/'.$role->id, $payload)
            ->assertNotFound();
    }

    public function test_admin_department_routes_use_public_id_and_keep_current_behavior(): void
    {
        $admin = $this->createAdminUser();
        $department = Department::factory()->create(['name' => 'Editable Department']);

        $this->assertStringContainsString((string) $department->public_id, route('admin.departments.edit', $department));
        $this->assertStringNotContainsString('/'.$department->id.'/edit', route('admin.departments.edit', $department));

        $this->actingAs($admin)
            ->get(route('admin.departments.edit', $department))
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/departments/'.$department->id.'/edit')
            ->assertNotFound();

        $this->actingAs($admin)
            ->patch(route('admin.departments.update', $department), [
                'name' => 'Updated Department',
                'lead_user_id' => null,
                'categories' => ['Sales'],
                'positions_name' => ['Manager'],
                'positions_category_id' => [''],
            ])
            ->assertRedirect(route('admin.departments.edit', $department));

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Updated Department',
        ]);

        $this->actingAs($admin)
            ->patch('/admin/departments/'.$department->id, [
                'name' => 'Numeric Department',
                'lead_user_id' => null,
            ])
            ->assertNotFound();
    }

    public function test_admin_public_id_routes_still_require_admin_permissions(): void
    {
        $userWithoutAdmin = $this->createUserWithRole([
            'can_admin' => false,
            'admin_users_org_manage' => false,
        ]);
        $managedUser = User::factory()->create([
            'is_active' => true,
            'force_password_change' => false,
        ]);
        $role = Role::factory()->create();
        $department = Department::factory()->create();

        $this->actingAs($userWithoutAdmin)
            ->get(route('admin.users.edit', $managedUser))
            ->assertForbidden();

        $this->actingAs($userWithoutAdmin)
            ->get(route('admin.roles.edit', $role))
            ->assertForbidden();

        $this->actingAs($userWithoutAdmin)
            ->get(route('admin.departments.edit', $department))
            ->assertForbidden();
    }

    private function rolePayload(array $overrides = []): array
    {
        $payload = [
            'name' => 'Regression Role',
            'can_analytics' => '0',
            'analytics_show_kpi' => '0',
            'analytics_show_charts' => '0',
            'analytics_show_tables' => '0',
            'analytics_finance_access' => '0',
            'can_orders' => '0',
            'orders_calculation' => '0',
            'orders_calc_save' => '0',
            'orders_calc_purchase_visible' => '0',
            'orders_proposals' => '0',
            'orders_list_scope' => 'own',
            'orders_list_purchase_visible' => '0',
            'orders_edit' => '0',
            'orders_list_edit' => '0',
            'orders_clients_manage' => '0',
            'can_price' => '0',
            'price_create_item' => '0',
            'price_deactivate_item' => '0',
            'price_delete_item' => '0',
            'price_purchase_access' => '0',
            'price_card_access' => '0',
            'price_card_edit' => '0',
            'price_card_history' => '0',
            'can_admin' => '0',
            'admin_reference_manage' => '0',
            'admin_users_org_manage' => '0',
        ];

        return array_merge($payload, $overrides);
    }

    public function test_dashboard_links_point_to_public_proposal_urls(): void
    {
        $user = $this->createUserWithRole([
            'can_analytics' => true,
            'analytics_show_tables' => true,
            'can_orders' => true,
            'orders_proposals' => true,
            'orders_list_scope' => 'all',
        ]);
        $proposal = OrderProposal::factory()->forUser($user)->create([
            'total_cost' => 1000,
            'payload' => OrderProposal::factory()->payload(1000),
        ]);
        $autosavedProposal = OrderProposal::factory()->forUser($user)->create([
            'is_autosaved' => true,
            'autosaved_by' => $user->id,
            'autosaved_at' => now(),
            'autosave_token' => 'dashboard-autosave-token',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('orders.proposals.show', $proposal), false)
            ->assertSee(route('orders.proposals.show', $autosavedProposal), false)
            ->assertDontSee('href="'.url('/orders/proposals/'.$proposal->id).'"', false);
    }
}
