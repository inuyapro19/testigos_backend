<?php

namespace App\Http\Requests\Cases;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === 'victim';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:50',
            'category' => 'required|string|in:laboral,despido_injustificado,discriminacion,acoso_laboral,horas_extras,accidente_trabajo',
            'company' => 'nullable|string|max:255',
            'funding_goal' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es requerido',
            'title.max' => 'El título no puede tener más de 255 caracteres',
            'description.required' => 'La descripción es requerida',
            'description.min' => 'La descripción debe tener al menos 50 caracteres',
            'category.required' => 'La categoría es requerida',
            'category.in' => 'La categoría seleccionada no es válida',
            'funding_goal.numeric' => 'El objetivo de financiamiento debe ser un número',
            'funding_goal.min' => 'El objetivo de financiamiento debe ser mayor a 0',
        ];
    }
}
