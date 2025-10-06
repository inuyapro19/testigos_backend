<?php

namespace App\Http\Requests\Cases;

use App\Enums\CaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'lawyer' || $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(CaseStatus::class)],
            'legal_analysis' => ['sometimes', 'string', 'min:100', 'max:5000'],
            'funding_goal' => ['sometimes', 'numeric', 'min:5000000', 'max:500000000'],
            'success_rate' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'expected_return' => ['sometimes', 'numeric', 'min:5', 'max:50'],
            'deadline' => ['sometimes', 'date', 'after:30 days', 'before:1 year'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'El estado seleccionado no es válido',
            'legal_analysis.min' => 'El análisis legal debe tener al menos 100 caracteres',
            'legal_analysis.max' => 'El análisis legal no puede exceder 5000 caracteres',
            'funding_goal.min' => 'El objetivo de financiamiento debe ser al menos $5.000.000',
            'funding_goal.max' => 'El objetivo de financiamiento no puede exceder $500.000.000',
            'success_rate.min' => 'El porcentaje de éxito debe ser al menos 1%',
            'success_rate.max' => 'El porcentaje de éxito no puede exceder 100%',
            'expected_return.min' => 'El retorno esperado debe ser al menos 5%',
            'expected_return.max' => 'El retorno esperado no puede exceder 50%',
            'deadline.after' => 'La fecha límite debe ser al menos 30 días en el futuro',
            'deadline.before' => 'La fecha límite no puede ser más de 1 año en el futuro',
        ];
    }
}