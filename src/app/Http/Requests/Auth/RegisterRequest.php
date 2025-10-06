<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'rut' => 'required|string|unique:users',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'phone' => 'required|string',
            'role' => 'required|in:victim,lawyer,investor',

            // Lawyer specific fields
            'license_number' => 'required_if:role,lawyer|string|unique:lawyer_profiles',
            'law_firm' => 'nullable|string',
            'specializations' => 'required_if:role,lawyer|array',
            'years_experience' => 'required_if:role,lawyer|integer|min:0',
            'bio' => 'nullable|string',

            // Investor specific fields
            'investor_type' => 'required_if:role,investor|in:individual,institutional,accredited',
            'minimum_investment' => 'nullable|numeric|min:0',
            'maximum_investment' => 'nullable|numeric|min:0',
            'investment_preferences' => 'nullable|array',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido',
            'email.required' => 'El correo electrónico es requerido',
            'email.email' => 'El correo electrónico debe ser válido',
            'email.unique' => 'Este correo electrónico ya está registrado',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'rut.required' => 'El RUT es requerido',
            'rut.unique' => 'Este RUT ya está registrado',
            'phone.required' => 'El teléfono es requerido',
            'role.required' => 'El rol es requerido',
            'role.in' => 'El rol debe ser: victim, lawyer o investor',
            'license_number.required_if' => 'El número de licencia es requerido para abogados',
            'specializations.required_if' => 'Las especializaciones son requeridas para abogados',
            'years_experience.required_if' => 'Los años de experiencia son requeridos para abogados',
            'investor_type.required_if' => 'El tipo de inversor es requerido',
        ];
    }
}
