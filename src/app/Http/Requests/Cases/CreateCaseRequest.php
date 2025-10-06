<?php

namespace App\Http\Requests\Cases;

use App\Data\CaseData;
use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'victim';
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:50', 'max:2000'],
            'category' => ['required', 'string', Rule::in([
                'Seguros', 'Retail', 'Transporte', 'Laboral', 'Servicios', 'Salud', 'Otros'
            ])],
            'company' => ['required', 'string', 'max:255'],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png,mp4,mp3', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio',
            'title.max' => 'El título no puede exceder 255 caracteres',
            'description.required' => 'La descripción es obligatoria',
            'description.min' => 'La descripción debe tener al menos 50 caracteres',
            'description.max' => 'La descripción no puede exceder 2000 caracteres',
            'category.required' => 'La categoría es obligatoria',
            'category.in' => 'La categoría seleccionada no es válida',
            'company.required' => 'La empresa es obligatoria',
            'documents.max' => 'No puedes subir más de 10 documentos',
            'documents.*.file' => 'Cada documento debe ser un archivo válido',
            'documents.*.mimes' => 'Los documentos deben ser PDF, DOC, DOCX, JPG, JPEG, PNG, MP4 o MP3',
            'documents.*.max' => 'Cada documento no puede exceder 10MB',
        ];
    }

    public function toData(): CaseData
    {
        return CaseData::fromRequest([
            ...$this->validated(),
            'victim_id' => $this->user()->id,
        ]);
    }
}