<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CaseModel;
use App\Models\LawyerProfile;
use App\Models\InvestorProfile;
use App\Models\Investment;
use App\Models\CaseDocument;
use App\Models\CaseUpdate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear roles y permisos usando PermissionsSeeder
        $this->call(PermissionsSeeder::class);

        // Crear usuarios de prueba con roles específicos
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@testigos.cl',
            'password' => Hash::make('password'),
            'rut' => '11111111-1',
            'phone' => '+56912345678',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $victim = User::create([
            'name' => 'María González',
            'email' => 'maria@testigos.cl',
            'password' => Hash::make('password'),
            'rut' => '22222222-2',
            'phone' => '+56987654321',
            'role' => 'victim',
            'is_active' => true,
        ]);
        $victim->assignRole('victim');

        $lawyer = User::create([
            'name' => 'Carlos Abogado',
            'email' => 'carlos@testigos.cl',
            'password' => Hash::make('password'),
            'rut' => '33333333-3',
            'phone' => '+56912345679',
            'role' => 'lawyer',
            'is_active' => true,
        ]);
        $lawyer->assignRole('lawyer');

        $investor = User::create([
            'name' => 'Pedro Inversor',
            'email' => 'pedro@testigos.cl',
            'password' => Hash::make('password'),
            'rut' => '44444444-4',
            'phone' => '+56912345680',
            'role' => 'investor',
            'is_active' => true,
        ]);
        $investor->assignRole('investor');

        // Crear perfil de abogado para Carlos
        LawyerProfile::create([
            'user_id' => $lawyer->id,
            'license_number' => 'CHL-123456',
            'law_firm' => 'Abogados Laborales Chile',
            'specializations' => ['Derecho Laboral', 'Despidos', 'Indemnizaciones'],
            'years_experience' => 10,
            'bio' => 'Abogado especializado en derecho laboral con más de 10 años de experiencia.',
            'success_rate' => 85.5,
            'cases_handled' => 45,
            'total_recovered' => 15000000,
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        // Crear perfil de inversor para Pedro
        InvestorProfile::create([
            'user_id' => $investor->id,
            'investor_type' => 'individual',
            'total_invested' => 2000000,
            'total_returns' => 2500000,
            'active_investments' => 3,
            'completed_investments' => 5,
            'average_return_rate' => 25.0,
            'investment_preferences' => ['laboral', 'despido_injustificado'],
            'minimum_investment' => 100000,
            'maximum_investment' => 1000000,
            'is_accredited' => true,
            'accredited_at' => now(),
        ]);

        // Crear víctimas adicionales
        $victims = User::factory(10)->create(['role' => 'victim']);
        foreach ($victims as $v) {
            $v->assignRole('victim');
        }

        // Crear abogados adicionales con sus perfiles
        $lawyers = User::factory(5)->create(['role' => 'lawyer']);
        foreach ($lawyers as $l) {
            $l->assignRole('lawyer');
            LawyerProfile::factory()->create(['user_id' => $l->id]);
        }

        // Crear inversores adicionales con sus perfiles
        $investors = User::factory(15)->create(['role' => 'investor']);
        foreach ($investors as $i) {
            $i->assignRole('investor');
            InvestorProfile::factory()->create(['user_id' => $i->id]);
        }

        // Crear caso de prueba específico
        $case1 = CaseModel::create([
            'title' => 'Despido injustificado durante licencia médica',
            'description' => 'Fui despedida de mi trabajo en una empresa de retail mientras estaba con licencia médica por estrés laboral. Considero que el despido fue injustificado y discriminatorio.',
            'victim_id' => $victim->id,
            'lawyer_id' => $lawyer->id,
            'status' => 'published',  // Using correct status from CaseStatus enum
            'category' => 'despido_injustificado',
            'company' => 'Retail Chile S.A.',
            'funding_goal' => 1500000,
            'current_funding' => 750000,
            'success_rate' => 85,
            'expected_return' => 30,
            'deadline' => now()->addMonths(3),
            'legal_analysis' => 'El caso tiene altas probabilidades de éxito dado que el despido durante licencia médica está protegido por la ley laboral chilena.',
            'evaluation_data' => [
                'case_strength' => 'alta',
                'estimated_duration' => '8 meses',
                'success_probability' => '85%',
            ],
        ]);

        // Documentos para el caso 1
        CaseDocument::factory(3)->create(['case_id' => $case1->id]);

        // Inversiones para el caso 1
        Investment::factory(5)->confirmed()->create([
            'case_id' => $case1->id,
            'investor_id' => $investors->random()->id,
        ]);

        // Actualizaciones para el caso 1
        CaseUpdate::factory(2)->create([
            'case_id' => $case1->id,
            'user_id' => $lawyer->id,
        ]);

        // Crear más casos con diferentes estados
        $allVictims = $victims->concat([$victim]);
        $allLawyers = $lawyers->concat([$lawyer]);
        $allInvestors = $investors->concat([$investor]);

        // 10 casos funded (financiados)
        CaseModel::factory(10)
            ->funded()
            ->create([
                'victim_id' => $allVictims->random()->id,
                'lawyer_id' => $allLawyers->random()->id,
            ])
            ->each(function ($case) use ($allInvestors) {
                // Agregar documentos
                CaseDocument::factory(rand(2, 5))->create(['case_id' => $case->id]);

                // Agregar inversiones
                Investment::factory(rand(3, 8))->confirmed()->create([
                    'case_id' => $case->id,
                    'investor_id' => $allInvestors->random()->id,
                ]);

                // Agregar actualizaciones
                CaseUpdate::factory(rand(1, 3))->create([
                    'case_id' => $case->id,
                    'user_id' => $case->lawyer_id,
                ]);
            });

        // 5 casos publicados
        CaseModel::factory(5)
            ->published()
            ->create([
                'victim_id' => $allVictims->random()->id,
                'lawyer_id' => $allLawyers->random()->id,
                'current_funding' => 0,
            ])
            ->each(function ($case) {
                CaseDocument::factory(rand(2, 4))->create(['case_id' => $case->id]);
            });

        // 3 casos completados
        CaseModel::factory(3)
            ->completed()
            ->create([
                'victim_id' => $allVictims->random()->id,
                'lawyer_id' => $allLawyers->random()->id,
            ])
            ->each(function ($case) use ($allInvestors) {
                CaseDocument::factory(rand(3, 6))->create(['case_id' => $case->id]);

                Investment::factory(rand(5, 10))->completed()->create([
                    'case_id' => $case->id,
                    'investor_id' => $allInvestors->random()->id,
                ]);

                CaseUpdate::factory(rand(3, 6))->create([
                    'case_id' => $case->id,
                    'user_id' => $case->lawyer_id,
                ]);
            });

        // Casos en proceso de bidding/licitación
        // 2 casos aprobados para licitación (recién aprobados, esperando bids)
        CaseModel::factory(2)->create([
            'victim_id' => $allVictims->random()->id,
            'status' => 'approved_for_bidding',
            'lawyer_id' => null, // Sin abogado asignado aún
            'funding_goal' => 0,
            'current_funding' => 0,
            'success_rate' => null,
            'expected_return' => null,
            'deadline' => null,
        ])->each(function ($case) {
            CaseDocument::factory(rand(2, 4))->create(['case_id' => $case->id]);
        });

        // 3 casos recibiendo licitaciones (activamente recibiendo propuestas)
        CaseModel::factory(3)->create([
            'victim_id' => $allVictims->random()->id,
            'status' => 'receiving_bids',
            'lawyer_id' => null,
            'funding_goal' => 0,
            'current_funding' => 0,
            'success_rate' => null,
            'expected_return' => null,
            'deadline' => null,
        ])->each(function ($case) {
            CaseDocument::factory(rand(3, 5))->create(['case_id' => $case->id]);
        });

        // 2 casos con licitaciones cerradas (evaluando propuestas)
        CaseModel::factory(2)->create([
            'victim_id' => $allVictims->random()->id,
            'status' => 'bids_closed',
            'lawyer_id' => null,
            'funding_goal' => 0,
            'current_funding' => 0,
            'success_rate' => null,
            'expected_return' => null,
            'deadline' => null,
        ])->each(function ($case) {
            CaseDocument::factory(rand(3, 5))->create(['case_id' => $case->id]);
        });

        // 2 casos con abogado asignado (listo para publicar a inversores)
        CaseModel::factory(2)->create([
            'victim_id' => $allVictims->random()->id,
            'lawyer_id' => $allLawyers->random()->id,
            'status' => 'lawyer_assigned',
            'funding_goal' => rand(5000000, 15000000),
            'current_funding' => 0,
            'success_rate' => rand(60, 90),
            'expected_return' => rand(20, 40),
            'deadline' => now()->addMonths(rand(2, 6)),
        ])->each(function ($case) {
            CaseDocument::factory(rand(3, 5))->create(['case_id' => $case->id]);
        });

        // Crear licitaciones de abogados para casos en proceso de bidding
        $this->call(LawyerBidsSeeder::class);

        $this->command->info('✅ Base de datos poblada exitosamente!');
        $this->command->info('📧 Usuarios de prueba:');
        $this->command->info('   Admin: admin@testigos.cl / password');
        $this->command->info('   Víctima: maria@testigos.cl / password');
        $this->command->info('   Abogado: carlos@testigos.cl / password');
        $this->command->info('   Inversor: pedro@testigos.cl / password');
    }

}
