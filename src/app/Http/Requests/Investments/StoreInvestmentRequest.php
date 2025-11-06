<?php

namespace App\Http\Requests\Investments;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvestmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === 'investor';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'case_id' => 'required|exists:cases,id',
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'required|in:webpay,transferencia,khipu',
            'transaction_id' => 'nullable|string',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'case_id.required' => 'El caso es requerido',
            'case_id.exists' => 'El caso seleccionado no existe',
            'amount.required' => 'El monto es requerido',
            'amount.numeric' => 'El monto debe ser un número',
            'amount.min' => 'El monto mínimo de inversión es $10.000',
            'payment_method.required' => 'El método de pago es requerido',
            'payment_method.in' => 'El método de pago debe ser: webpay, transferencia o khipu',
        ];
    }
}
