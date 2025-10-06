<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaseDocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Documents for Case 1 (SUBMITTED)
        DB::table('case_documents')->insert([
            'case_id' => 1,
            'name' => 'Boleta_Movistar_Enero_2025.pdf',
            'original_name' => 'Boleta Movistar Enero 2025.pdf',
            'file_path' => '/storage/documents/case_1/boleta_movistar_enero.pdf',
            'file_type' => 'pdf',
            'file_size' => 234567,
            'mime_type' => 'application/pdf',
            'document_type' => 'billing',
            'description' => 'Boleta con cobro indebido del servicio',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        DB::table('case_documents')->insert([
            'case_id' => 1,
            'name' => 'Emails_Reclamo_Movistar.pdf',
            'original_name' => 'Emails Reclamo Movistar.pdf',
            'file_path' => '/storage/documents/case_1/emails_reclamo.pdf',
            'file_type' => 'pdf',
            'file_size' => 156789,
            'mime_type' => 'application/pdf',
            'document_type' => 'correspondence',
            'description' => 'Correos electrónicos de reclamo enviados a Movistar',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        // Documents for Case 3 (PUBLISHED - Consalud)
        DB::table('case_documents')->insert([
            'case_id' => 3,
            'name' => 'Contrato_Isapre_Consalud.pdf',
            'original_name' => 'Contrato Isapre Consalud.pdf',
            'file_path' => '/storage/documents/case_3/contrato_isapre.pdf',
            'file_type' => 'pdf',
            'file_size' => 543210,
            'mime_type' => 'application/pdf',
            'document_type' => 'contract',
            'description' => 'Contrato original con Consalud',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        DB::table('case_documents')->insert([
            'case_id' => 3,
            'name' => 'Carta_Rechazo_Cobertura.pdf',
            'original_name' => 'Carta Rechazo Cobertura.pdf',
            'file_path' => '/storage/documents/case_3/carta_rechazo.pdf',
            'file_type' => 'pdf',
            'file_size' => 123456,
            'mime_type' => 'application/pdf',
            'document_type' => 'official_document',
            'description' => 'Carta de rechazo de cobertura por parte de Consalud',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        DB::table('case_documents')->insert([
            'case_id' => 3,
            'name' => 'Informes_Medicos.pdf',
            'original_name' => 'Informes Médicos.pdf',
            'file_path' => '/storage/documents/case_3/informes_medicos.pdf',
            'file_type' => 'pdf',
            'file_size' => 987654,
            'mime_type' => 'application/pdf',
            'document_type' => 'medical',
            'description' => 'Informes médicos que respaldan el tratamiento',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        // Documents for Case 7 (COMPLETED)
        DB::table('case_documents')->insert([
            'case_id' => 7,
            'name' => 'Sentencia_Favorable.pdf',
            'original_name' => 'Sentencia Favorable.pdf',
            'file_path' => '/storage/documents/case_7/sentencia.pdf',
            'file_type' => 'pdf',
            'file_size' => 678901,
            'mime_type' => 'application/pdf',
            'document_type' => 'legal_ruling',
            'description' => 'Sentencia judicial favorable al consumidor',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);
    }
}
