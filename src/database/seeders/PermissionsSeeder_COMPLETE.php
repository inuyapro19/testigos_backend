<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $this->createCasesPermissions();
        $this->createInvestmentsPermissions();
        $this->createUsersPermissions();
        $this->createLawyersPermissions();
        $this->createInvestorsPermissions();
        $this->createTransactionsPermissions();
        $this->createWithdrawalsPermissions();
        $this->createDashboardPermissions();
        $this->createSystemPermissions();

        // Create Roles and Assign Permissions
        $this->createRoles();

        $this->command->info('✅ Roles and permissions created successfully!');
    }

    private function createCasesPermissions(): void
    {
        $permissions = [
            // View permissions
            'cases.view.all',
            'cases.view.own',
            'cases.view.published',
            'cases.view.assigned',
            'cases.view.pending',

            // CRUD permissions
            'cases.create',
            'cases.edit.own',
            'cases.edit.any',
            'cases.delete.own',
            'cases.delete.any',

            // Workflow permissions
            'cases.assign.lawyer',
            'cases.evaluate',
            'cases.publish',
            'cases.start',
            'cases.close',
            'cases.distribute.returns',

            // Documents
            'cases.documents.upload',
            'cases.documents.view',
            'cases.documents.delete',

            // Updates
            'cases.updates.create',
            'cases.updates.view',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }

    private function createInvestmentsPermissions(): void
    {
        $permissions = [
            // View permissions
            'investments.view.all',
            'investments.view.own',
            'investments.view.case',

            // CRUD permissions
            'investments.create',
            'investments.edit.status',
            'investments.cancel.own',
            'investments.cancel.any',

            // Statistics
            'investments.statistics.own',
            'investments.statistics.all',
            'investments.opportunities',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }

    private function createUsersPermissions(): void
    {
        $permissions = [
            // View permissions
            'users.view.all',
            'users.view.profile',

            // Edit permissions
            'users.edit.own.profile',
            'users.edit.any.profile',
            'users.edit.status',
            'users.delete',

            // Roles management
            'users.roles.view',
            'users.roles.assign',
            'users.roles.remove',

            // Permissions management
            'users.permissions.view',
            'users.permissions.assign',
            'users.permissions.remove',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }

    private function createLawyersPermissions(): void
    {
        $permissions = [
            'lawyers.profile.view',
            'lawyers.profile.edit.own',
            'lawyers.verify',
            'lawyers.statistics.view',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }

    private function createInvestorsPermissions(): void
    {
        $permissions = [
            'investors.profile.view',
            'investors.profile.edit.own',
            'investors.accredit',
            'investors.statistics.view',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }

    private function createTransactionsPermissions(): void
    {
        $permissions = [
            'transactions.view.all',
            'transactions.view.own',
            'transactions.view.case',
            'transactions.statistics',
            'transactions.export',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }

    private function createWithdrawalsPermissions(): void
    {
        $permissions = [
            // View permissions
            'withdrawals.view.all',
            'withdrawals.view.own',

            // CRUD permissions
            'withdrawals.create',
            'withdrawals.approve',
            'withdrawals.reject',
            'withdrawals.process',
            'withdrawals.complete',
            'withdrawals.cancel.own',

            // Balance & Statistics
            'withdrawals.balance.view.own',
            'withdrawals.statistics',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }

    private function createDashboardPermissions(): void
    {
        $permissions = [
            'dashboard.admin.view',
            'dashboard.lawyer.view',
            'dashboard.investor.view',
            'dashboard.victim.view',

            'analytics.cases',
            'analytics.investments',
            'analytics.financial',
            'analytics.users',
            'analytics.export',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }

    private function createSystemPermissions(): void
    {
        $permissions = [
            'system.settings.view',
            'system.settings.edit',
            'system.logs.view',
            'system.audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }

    private function createRoles(): void
    {
        // Admin Role - Full Access
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Victim Role
        $victim = Role::create(['name' => 'victim']);
        $victim->givePermissionTo([
            // Cases
            'cases.view.own',
            'cases.view.published',
            'cases.create',
            'cases.edit.own',
            'cases.delete.own',
            'cases.documents.upload',
            'cases.documents.view',
            'cases.updates.view',

            // Profile
            'users.view.profile',
            'users.edit.own.profile',

            // Dashboard
            'dashboard.victim.view',

            // Investments (view only)
            'investments.view.own',

            // Transactions
            'transactions.view.own',
        ]);

        // Lawyer Role
        $lawyer = Role::create(['name' => 'lawyer']);
        $lawyer->givePermissionTo([
            // Cases - Full workflow
            'cases.view.all',
            'cases.view.own',
            'cases.view.assigned',
            'cases.view.pending',
            'cases.edit.own',
            'cases.assign.lawyer',
            'cases.evaluate',
            'cases.publish',
            'cases.start',
            'cases.close',
            'cases.documents.upload',
            'cases.documents.view',
            'cases.documents.delete',
            'cases.updates.create',
            'cases.updates.view',

            // Investments - View for assigned cases
            'investments.view.case',

            // Profile
            'users.view.profile',
            'users.edit.own.profile',
            'lawyers.profile.view',
            'lawyers.profile.edit.own',
            'lawyers.statistics.view',

            // Dashboard
            'dashboard.lawyer.view',

            // Transactions
            'transactions.view.own',
            'transactions.view.case',

            // Withdrawals
            'withdrawals.view.own',
            'withdrawals.create',
            'withdrawals.balance.view.own',
            'withdrawals.cancel.own',
        ]);

        // Investor Role
        $investor = Role::create(['name' => 'investor']);
        $investor->givePermissionTo([
            // Cases - Published only
            'cases.view.published',
            'cases.documents.view',
            'cases.updates.view',

            // Investments - Full control
            'investments.view.own',
            'investments.create',
            'investments.cancel.own',
            'investments.statistics.own',
            'investments.opportunities',

            // Profile
            'users.view.profile',
            'users.edit.own.profile',
            'investors.profile.view',
            'investors.profile.edit.own',
            'investors.statistics.view',

            // Dashboard
            'dashboard.investor.view',

            // Transactions
            'transactions.view.own',

            // Withdrawals
            'withdrawals.view.own',
            'withdrawals.create',
            'withdrawals.balance.view.own',
            'withdrawals.cancel.own',
        ]);
    }
}
