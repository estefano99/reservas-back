<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

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

    //Esta funcion deberia ser una tabla slots con fecha y rango de horario que crea el admin, pero lo deje en una funcion porque no me daba tanto los tiempos y el enunciado no lo pedia.
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('start_time') && $this->has('end_time')) {
                $start = Carbon::parse($this->start_time);
                $end = Carbon::parse($this->end_time);

                $startHour = (int) $start->format('H');
                $endHour = (int) $end->format('H');

                // Horario permitido: de 08:00 a 20:00
                if ($startHour < 8 || $endHour > 20) {
                    $validator->errors()->add('start_time', 'Las reservas deben estar entre las 08:00 y las 20:00.');
                }
                // Verificar que la duración de la reserva sea de 1 hora exacta
                if ($start->diffInMinutes($end) !== 60) {
                    $validator->errors()->add('end_time', 'La duración de la reserva debe ser de 1 hora exacta.');
                }
            }
        });
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
