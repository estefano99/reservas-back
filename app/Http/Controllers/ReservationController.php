<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationStatusRequest;
use App\Models\Reservation;
use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{

    public function index(Request $request)
    {
        $reservations = $request->user()
            ->reservations()
            ->with('space')
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'data' => $reservations
        ]);
    }

    public function store(StoreReservationRequest $request)
    {
        $data = $request->validated();

        // Validar que no haya reservas en conflicto para ese espacio ya sea:
        // el start_time o el end_time dentro del rango de tiempo de una ya existente
        $conflict = Reservation::where('space_id', $data['space_id'])
            ->whereIn('status', ['pending', 'approved']) // Mientras este asi no se puede reserva
            ->where(function ($query) use ($data) {
                $query->where('start_time', '<', $data['end_time'])
                    ->where('end_time', '>', $data['start_time']);
            })
            ->exists();



        if ($conflict) {
            return response()->json(['message' => 'Ya existe una reserva para ese espacio en ese horario'], 409);
        }

        $reservation = Reservation::create([
            'user_id' => $request->user()->id,
            'space_id' => $data['space_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Reserva creada exitosamente y en espera de aprobación',
            'data' => $reservation
        ], 201);
    }

    public function destroy(Reservation $reservation)
    {
        $user = request()->user();

        if ($reservation->user_id !== $user->id) {
            return response()->json(['message' => 'No tienes permiso para cancelar esta reserva'], 403);
        }

        if ($reservation->status !== 'pending') {
            return response()->json(['message' => 'Solo se pueden cancelar reservas pendientes'], 400);
        }

        $reservation->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Reserva cancelada correctamente']);
    }

    public function getPending()
    {
        $reservations = Reservation::with('user', 'space')
            ->where('status', 'pending')
            ->orderBy('start_time')
            ->get();

        return response()->json(['data' => $reservations]);
    }

    public function updateStatus(UpdateReservationStatusRequest $request, Reservation $reservation)
    {
        if ($reservation->status !== 'pending') {
            return response()->json(['message' => 'Solo se pueden modificar reservas pendientes'], 400);
        }

        $reservation->update([
            'status' => $request->validated()['status'],
        ]);

        return response()->json(['message' => 'Estado actualizado correctamente']);
    }

    //Esta funcion devuelve los slots disponibles en base al espacio y fecha seleccionados del front
    //En vez de crear una tabla slots con los rangos de horarios y la fecha, lo hice asi para tratar de cumplir con el tiempo que me requirieron de entrega.
    public function availableSlots(Request $request)
    {
        $spaceId = $request->query('space_id');
        $date = $request->query('date');

        if (!$spaceId || !$date) {
            return response()->json(['message' => 'Parámetros space_id y date son requeridos'], 400);
        }
        $reservations = Reservation::with('space')
            ->where('space_id', $spaceId)
            ->whereDate('start_time', $date)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->get();

        Log::info($reservations);

        $slots = [];

        //Simulo que todos los dias tienen 8 horas de apertura
        $startHour = 8;
        $endHour = 18;

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $slotStart = Carbon::createFromFormat('Y-m-d H:i:s', "$date $hour:00:00");
            $slotEnd = Carbon::createFromFormat('Y-m-d H:i:s', "$date " . ($hour + 1) . ":00:00");

            $isTaken = $reservations->contains(function ($res) use ($slotStart, $slotEnd) {
                return (
                    ($slotStart >= $res->start_time && $slotStart < $res->end_time) ||
                    ($slotEnd > $res->start_time && $slotEnd <= $res->end_time) ||
                    ($slotStart <= $res->start_time && $slotEnd >= $res->end_time)
                );
            });

            $slots[] = [
                'start_time' => $slotStart->format('H:i:s'),
                'end_time' => $slotEnd->format('H:i:s'),
                'available' => !$isTaken
            ];
        }


        return response()->json([
            'data' => $slots
        ]);
    }
}
