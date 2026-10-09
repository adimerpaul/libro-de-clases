<?php

namespace App\Http\Controllers;

use App\Models\Internacion;
use App\Models\InternacionItem;
use Illuminate\Http\Request;

class InternacionItemController extends Controller
{
    public function store(Request $request, $internacionId)
    {
        $this->req($request, 'Crear Internaciones');
        $request->validate([
            'producto_id' => 'nullable|exists:productos,id',
            'nombre' => 'required|string|max:255',
            'cantidad' => 'required|numeric|min:0.01',
            'precio' => 'required|numeric|min:0',
        ]);

        $internacion = Internacion::findOrFail($internacionId);
        InternacionController::bloqueadaSiPagada($internacion);

        $item = InternacionItem::create([
            'internacion_id' => $internacionId,
            'producto_id' => $request->producto_id ?: null,
            'user_id' => $request->user()->id,
            'nombre' => $request->nombre,
            'cantidad' => $request->cantidad,
            'precio' => $request->precio,
            'total' => round($request->cantidad * $request->precio, 2),
        ]);

        return response()->json($item->load(['producto:id,nombre', 'user:id,name']), 201);
    }

    public function update(Request $request, $id)
    {
        $this->req($request, 'Editar Internaciones');
        $request->validate([
            'nombre' => 'required|string|max:255',
            'cantidad' => 'required|numeric|min:0.01',
            'precio' => 'required|numeric|min:0',
        ]);

        $item = InternacionItem::findOrFail($id);
        InternacionController::bloqueadaSiPagada($item->internacion);
        $item->update([
            'nombre' => $request->nombre,
            'cantidad' => $request->cantidad,
            'precio' => $request->precio,
            'total' => round($request->cantidad * $request->precio, 2),
        ]);

        return response()->json($item->load(['producto:id,nombre', 'user:id,name']));
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Internaciones');
        $item = InternacionItem::findOrFail($id);
        InternacionController::bloqueadaSiPagada($item->internacion);
        $item->delete();

        return response()->json(['message' => 'Cargo eliminado']);
    }

    private function req(Request $request, string|array $permission): void
    {
        $user = $request->user();
        $perms = is_array($permission) ? $permission : [$permission];
        foreach ($perms as $p) {
            if ($user->hasPermissionTo($p)) {
                return;
            }
        }
        abort(403, 'No tiene permiso para realizar esta acción');
    }
}
