<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, 'Ver Compras');

        $q       = $request->input('q', '');
        $perPage = $request->input('per_page');

        $query = Proveedor::orderBy('nombre');
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%$q%")
                   ->orWhere('nit', 'like', "%$q%")
                   ->orWhere('razon_social', 'like', "%$q%")
                   ->orWhere('contacto', 'like', "%$q%");
            });
        }

        if ($perPage) {
            return response()->json($query->paginate((int) $perPage));
        }
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $this->req($request, 'Crear Compras');
        $request->validate(['nombre' => 'required|string|max:255']);
        $proveedor = Proveedor::create([
            'nombre'       => mb_strtoupper($request->nombre),
            'nit'          => $request->nit ?: null,
            'razon_social' => $request->razon_social ? mb_strtoupper($request->razon_social) : null,
            'contacto'     => $request->contacto ? mb_strtoupper($request->contacto) : null,
            'telefono'     => $request->telefono ?: null,
            'email'        => $request->email ?: null,
            'direccion'    => $request->direccion ? mb_strtoupper($request->direccion) : null,
            'estado'       => $request->estado ? mb_strtoupper($request->estado) : 'ACTIVO',
            'observacion'  => $request->observacion ?: null,
        ]);
        return response()->json($proveedor, 201);
    }

    public function update(Request $request, $id)
    {
        $this->req($request, 'Editar Compras');
        $request->validate(['nombre' => 'required|string|max:255']);
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->update([
            'nombre'       => mb_strtoupper($request->nombre),
            'nit'          => $request->nit ?: null,
            'razon_social' => $request->razon_social ? mb_strtoupper($request->razon_social) : null,
            'contacto'     => $request->contacto ? mb_strtoupper($request->contacto) : null,
            'telefono'     => $request->telefono ?: null,
            'email'        => $request->email ?: null,
            'direccion'    => $request->direccion ? mb_strtoupper($request->direccion) : null,
            'estado'       => $request->estado ? mb_strtoupper($request->estado) : 'ACTIVO',
            'observacion'  => $request->observacion ?: null,
        ]);
        return response()->json($proveedor);
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Compras');
        Proveedor::findOrFail($id)->delete();
        return response()->json(['message' => 'Proveedor eliminado']);
    }

    private function req(Request $request, string|array $permission): void
    {
        $user  = $request->user();
        $perms = is_array($permission) ? $permission : [$permission];
        foreach ($perms as $p) {
            if ($user->hasPermissionTo($p)) return;
        }
        abort(403, 'No tiene permiso para realizar esta acción');
    }
}
