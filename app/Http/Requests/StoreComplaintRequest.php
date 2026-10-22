<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'claimant_national_id' => preg_replace('/\s+/', '', (string) $this->input('claimant_national_id')),
            'claimant_email' => strtolower(trim((string) $this->input('claimant_email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'claimant_name' => ['required', 'string', 'min:3', 'max:255'],
            'claimant_address' => ['required', 'string', 'max:500'],
            'claimant_national_id' => ['required', 'string', 'max:30', 'regex:/^\S+$/'],
            'claimant_email' => ['required', 'email:rfc', 'max:255'],
            'claimant_phone' => ['nullable', 'string', 'max:30'],
            'purchased_item' => ['required', 'string', 'min:3', 'max:500'],
            'claimed_amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'claim_type' => ['required', Rule::in(['Queja', 'Reclamo'])],
            'claim_details' => ['required', 'string', 'min:10', 'max:5000'],
            'claim_request' => ['required', 'string', 'min:10', 'max:5000'],
            'accept_privacy' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'claimant_name.required' => 'Los nombres y apellidos son obligatorios.',
            'claimant_name.min' => 'Ingresa los nombres y apellidos completos.',
            'claimant_address.required' => 'El domicilio es obligatorio.',
            'claimant_national_id.required' => 'El documento de identidad es obligatorio.',
            'claimant_national_id.regex' => 'El documento de identidad no debe contener espacios.',
            'claimant_email.required' => 'El correo electrónico es obligatorio.',
            'claimant_email.email' => 'Ingresa un correo electrónico válido.',
            'purchased_item.required' => 'La identificación del bien adquirido es obligatoria.',
            'claimed_amount.numeric' => 'El monto debe ser un número válido.',
            'claimed_amount.min' => 'El monto no puede ser negativo.',
            'claimed_amount.decimal' => 'El monto debe tener como máximo dos decimales.',
            'claim_type.required' => 'Selecciona el tipo de incidencia.',
            'claim_type.in' => 'El tipo debe ser Queja o Reclamo.',
            'claim_details.required' => 'El detalle es obligatorio.',
            'claim_details.min' => 'El detalle debe tener al menos 10 caracteres.',
            'claim_request.required' => 'El pedido es obligatorio.',
            'claim_request.min' => 'El pedido debe tener al menos 10 caracteres.',
            'accept_privacy.accepted' => 'Debes aceptar la política de privacidad.',
        ];
    }
}
