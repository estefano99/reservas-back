<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
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
            'space_id' => 'required|exists:spaces,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ];
    }

    public function messages(): array
    {
        return [
            'space_id.required' => 'El campo espacio es obligatorio.',
            'space_id.exists' => 'El espacio seleccionado no existe.',
            'start_time.required' => 'Debe indicar la fecha y hora de inicio.',
            'start_time.date' => 'El formato de la fecha de inicio no es válido.',
            'end_time.required' => 'Debe indicar la fecha y hora de fin.',
            'end_time.date' => 'El formato de la fecha de fin no es válido.',
            'end_time.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }
}
