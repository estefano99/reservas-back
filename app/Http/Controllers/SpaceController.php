<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSpaceRequest;
use App\Http\Requests\UpdateSpaceRequest;
use App\Models\Space;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    public function index()
    {
        $spaces = Space::where('available', true)->get();

        return response()->json($spaces);
    }

    public function store(StoreSpaceRequest $request)
    {
        $space = Space::create($request->validated());
        $message = 'Espacio creado exitosamente';

        return response()->json([
            'data' => $space,
            'message' => $message,
        ], 201);
    }

    public function update(UpdateSpaceRequest $request, Space $space)
    {

        $space->update($request->validated());
        $message = 'Espacio actualizado';
        return response()->json([
            'data' => $space,
            'message' => $message,
        ]);
    }

    public function destroy(Space $space)
    {
        $space->delete();

        return response()->json(['message' => 'Espacio eliminado']);
    }
}
