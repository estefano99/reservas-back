<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationStatusRequest;
use App\Models\Reservation;
use Illuminate\Http\Request;

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
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($data) {
                $query->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                    ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                    ->orWhere(function ($query) use ($data) {
                        $query->where('start_time', '<=', $data['start_time'])
                            ->where('end_time', '>=', $data['end_time']);
                    });
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

    public function pending()
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
}
