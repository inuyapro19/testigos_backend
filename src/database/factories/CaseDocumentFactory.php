<?php

namespace Database\Factories;

use App\Models\CaseDocument;
use App\Models\CaseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CaseDocumentFactory extends Factory
{
    protected $model = CaseDocument::class;

    public function definition(): array
    {
        $documentTypes = ['contrato', 'liquidacion', 'carta_despido', 'certificado_medico', 'prueba', 'otro'];
        $fileTypes = ['pdf', 'docx', 'jpg', 'png'];
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        $fileType = $this->faker->randomElement($fileTypes);
        $originalName = $this->faker->words(3, true) . '.' . $fileType;

        return [
            'case_id' => CaseModel::factory(),
            'name' => $this->faker->words(3, true),
            'original_name' => $originalName,
            'file_path' => 'documents/' . $this->faker->uuid() . '.' . $fileType,
            'file_type' => $fileType,
            'file_size' => $this->faker->numberBetween(10240, 5242880),
            'mime_type' => $mimeTypes[$fileType],
            'document_type' => $this->faker->randomElement($documentTypes),
            'description' => $this->faker->boolean(60) ? $this->faker->sentence() : null,
        ];
    }
}
