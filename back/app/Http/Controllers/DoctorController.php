<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Especialidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, ['Ver Doctores', 'Ver Ventas', 'Crear Ventas']);

        $q = $request->input('q', '');
        $estado = $request->input('estado', '');
        $perPage = $request->input('per_page');

        $query = Doctor::with('especialidades:id,nombre')->orderBy('nombre');

        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre', 'like', "%$q%")
                    ->orWhere('ci', 'like', "%$q%")
                    ->orWhere('registro', 'like', "%$q%");
            });
        }

        if ($estado) {
            $query->where('estado', $estado);
        }

        if ($perPage) {
            return response()->json($query->paginate((int) $perPage));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $this->req($request, ['Crear Doctores', 'Crear Ventas', 'Crear Solicitudes Laboratorio']);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'especialidad_ids' => 'nullable|array',
            'especialidad_ids.*' => 'exists:especialidades,id',
        ]);

        $doctor = DB::transaction(function () use ($request) {
            $doctor = Doctor::create([
                'nombre' => $request->nombre,
                'ci' => $request->ci ?: null,
                'telefono' => $request->telefono ?: null,
                'email' => $request->email ?: null,
                'registro' => $request->registro ?: null,
                'estado' => $request->estado ?: 'ACTIVO',
            ]);
            $doctor->especialidades()->sync($request->especialidad_ids ?: []);

            return $doctor;
        });

        return response()->json($doctor->load('especialidades:id,nombre'), 201);
    }

    public function update(Request $request, $id)
    {
        $this->req($request, 'Editar Doctores');

        $request->validate([
            'nombre' => 'required|string|max:255',
            'especialidad_ids' => 'nullable|array',
            'especialidad_ids.*' => 'exists:especialidades,id',
        ]);

        $doctor = Doctor::findOrFail($id);

        DB::transaction(function () use ($request, $doctor) {
            $doctor->update([
                'nombre' => $request->nombre,
                'ci' => $request->ci ?: null,
                'telefono' => $request->telefono ?: null,
                'email' => $request->email ?: null,
                'registro' => $request->registro ?: null,
                'estado' => $request->estado ?: 'ACTIVO',
            ]);
            $doctor->especialidades()->sync($request->especialidad_ids ?: []);
        });

        return response()->json($doctor->load('especialidades:id,nombre'));
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Doctores');
        Doctor::findOrFail($id)->delete();

        return response()->json(['message' => 'Doctor eliminado']);
    }

    public function pacientes(Request $request, $id)
    {
        $this->req($request, 'Ver Doctores');

        $doctor = Doctor::findOrFail($id);
        $perPage = (int) $request->input('per_page', 10);
        $q = trim((string) $request->input('q', ''));

        $query = $doctor->ventas()
            ->with(['paciente:id,nombre_completo,ci,telefono', 'seguro:id,nombre'])
            ->withCount('detalles')
            ->whereNotNull('paciente_id')
            ->when($q, function ($venta) use ($q) {
                $venta->whereHas('paciente', function ($paciente) use ($q) {
                    $paciente->where('nombre_completo', 'like', "%{$q}%")
                        ->orWhere('ci', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('fecha_hora');

        return response()->json([
            'doctor' => $doctor->only(['id', 'nombre']),
            'atenciones' => $query->paginate($perPage),
            'pacientes_unicos' => $doctor->ventas()
                ->whereNotNull('paciente_id')
                ->distinct()
                ->count('paciente_id'),
        ]);
    }

    // ── Especialidades ────────────────────────────────────────────

    public function especialidades(Request $request)
    {
        $this->req($request, ['Ver Doctores', 'Crear Doctores', 'Editar Doctores', 'Ver Ventas', 'Crear Ventas', 'Crear Solicitudes Laboratorio']);

        return response()->json(Especialidad::orderBy('nombre')->get());
    }

    public function storeEspecialidad(Request $request)
    {
        $this->req($request, ['Crear Doctores', 'Editar Doctores']);
        $request->validate(['nombre' => 'required|string|max:255']);

        $especialidad = Especialidad::firstOrCreate(['nombre' => mb_strtoupper($request->nombre)]);

        return response()->json($especialidad, 201);
    }

    // ── Helpers ───────────────────────────────────────────────────

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
