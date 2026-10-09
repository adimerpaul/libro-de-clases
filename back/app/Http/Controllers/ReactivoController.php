<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Reactivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReactivoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAny($request, 'Ver Reactivos');
        $query = Reactivo::with('servicios.producto:id,codigo,nombre')->latest('id');
        if ($q = trim((string) $request->input('q'))) {
            $query->where(fn ($sub) => $sub->where('nombre', 'like', "%{$q}%")->orWhere('codigo', 'like', "%{$q}%"));
        }

        return response()->json($query->paginate(min((int) $request->input('per_page', 15), 100)));
    }

    public function formData(Request $request)
    {
        $this->authorizeAny($request, ['Ver Reactivos', 'Crear Reactivos', 'Editar Reactivos']);

        return response()->json(['servicios' => Producto::whereHas('tipoProducto', fn ($q) => $q->where('es_laboratorio', true))
            ->orderBy('nombre')->get(['id', 'codigo', 'nombre'])]);
    }

    public function show(Request $request, Reactivo $reactivo)
    {
        $this->authorizeAny($request, 'Ver Reactivos');

        return response()->json($reactivo->load('servicios.producto:id,codigo,nombre'));
    }

    public function store(Request $request)
    {
        $this->authorizeAny($request, 'Crear Reactivos');
        $reactivo = DB::transaction(function () use ($request) {
            [$datos, $servicios] = $this->validated($request);
            $reactivo = Reactivo::create($datos);
            $this->syncServicios($reactivo, $servicios);

            return $reactivo;
        });

        return response()->json($reactivo->load('servicios.producto:id,codigo,nombre'), 201);
    }

    public function update(Request $request, Reactivo $reactivo)
    {
        $this->authorizeAny($request, 'Editar Reactivos');
        DB::transaction(function () use ($request, $reactivo) {
            [$datos, $servicios] = $this->validated($request);
            $reactivo->update($datos);
            $reactivo->servicios()->delete();
            $this->syncServicios($reactivo, $servicios);
        });

        return response()->json($reactivo->fresh()->load('servicios.producto:id,codigo,nombre'));
    }

    public function destroy(Request $request, Reactivo $reactivo)
    {
        $this->authorizeAny($request, 'Eliminar Reactivos');
        DB::transaction(function () use ($reactivo) {
            $reactivo->servicios()->delete();
            $reactivo->delete();
        });

        return response()->json(['message' => 'Reactivo eliminado']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:50'], 'nombre' => ['required', 'string', 'max:255'],
            'unidad' => ['required', 'string', 'max:30'], 'stock_actual' => ['required', 'numeric', 'min:0'],
            'stock_minimo' => ['required', 'numeric', 'min:0'], 'estado' => ['required', 'in:ACTIVO,INACTIVO'],
            'descripcion' => ['nullable', 'string', 'max:1000'], 'servicios' => ['array'],
            'servicios.*.producto_id' => ['required', 'integer', 'distinct', 'exists:productos,id'],
            'servicios.*.cantidad' => ['required', 'numeric', 'gt:0'],
        ]);

        return [collect($data)->except('servicios')->all(), $data['servicios'] ?? []];
    }

    private function syncServicios(Reactivo $reactivo, array $servicios): void
    {
        foreach ($servicios as $servicio) {
            $reactivo->servicios()->create($servicio);
        }
    }

    private function authorizeAny(Request $request, string|array $permissions): void
    {
        foreach ((array) $permissions as $permission) {
            if ($request->user()->hasPermissionTo($permission)) {
                return;
            }
        }
        abort(403, 'No tiene permiso para realizar esta acción');
    }
}
