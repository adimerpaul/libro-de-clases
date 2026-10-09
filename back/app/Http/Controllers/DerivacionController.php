<?php

namespace App\Http\Controllers;

use App\Models\Derivacion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DerivacionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAny($request, 'Ver Derivaciones');

        $query = Derivacion::with('user:id,name')->latest('fecha')->latest('id');

        if ($desde = $request->input('desde')) {
            $query->whereDate('fecha', '>=', $desde);
        }
        if ($hasta = $request->input('hasta')) {
            $query->whereDate('fecha', '<=', $hasta);
        }
        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($buscar = trim((string) $request->input('q'))) {
            $query->where(function ($subquery) use ($buscar) {
                $subquery->where('paciente', 'like', "%{$buscar}%")
                    ->orWhere('laboratorio_destino', 'like', "%{$buscar}%")
                    ->orWhere('servicio', 'like', "%{$buscar}%");
            });
        }

        return response()->json($query->paginate((int) $request->input('per_page', 15)));
    }

    public function formData(Request $request)
    {
        $this->authorizeAny($request, ['Ver Derivaciones', 'Crear Derivaciones', 'Editar Derivaciones']);

        return response()->json([
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Derivacion $derivacion)
    {
        $this->authorizeAny($request, 'Ver Derivaciones');

        return response()->json($derivacion->load('user:id,name'));
    }

    public function store(Request $request)
    {
        $this->authorizeAny($request, 'Crear Derivaciones');
        $validated = $this->validatePayload($request, true);
        $validated['user_id'] = $request->user()->id;
        $validated['imagen'] = $this->storeImage($request);

        return response()->json(Derivacion::create($validated)->load('user:id,name'), 201);
    }

    public function update(Request $request, Derivacion $derivacion)
    {
        $this->authorizeAny($request, 'Editar Derivaciones');
        $validated = $this->validatePayload($request, false);

        if ($request->hasFile('imagen')) {
            $imagenAnterior = $derivacion->imagen;
            $validated['imagen'] = $this->storeImage($request);
            $this->deleteImage($imagenAnterior);
        }

        $derivacion->update($validated);

        return response()->json($derivacion->fresh()->load('user:id,name'));
    }

    public function destroy(Request $request, Derivacion $derivacion)
    {
        $this->authorizeAny($request, 'Eliminar Derivaciones');
        $derivacion->delete();

        return response()->json(['message' => 'Derivación eliminada']);
    }

    public function pdf(Request $request, Derivacion $derivacion)
    {
        $this->authorizeAny($request, 'Ver Derivaciones');
        $derivacion->load('user:id,name');

        return Pdf::loadView('reportes.derivacion', compact('derivacion'))
            ->setPaper('letter')
            ->stream('derivacion_'.$derivacion->id.'.pdf');
    }

    public function image(Request $request, Derivacion $derivacion)
    {
        $this->authorizeAny($request, 'Ver Derivaciones');
        $path = public_path('images/derivaciones/'.$derivacion->imagen);
        abort_unless(is_file($path), 404);

        return response()->file($path);
    }

    private function validatePayload(Request $request, bool $imageRequired): array
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'paciente' => 'required|string|max:255',
            'laboratorio_destino' => 'nullable|string|max:255',
            'servicio' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
            'imagen' => ($imageRequired ? 'required|' : 'nullable|').'image|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        foreach (['paciente', 'laboratorio_destino', 'servicio', 'observaciones'] as $field) {
            if (! empty($validated[$field])) {
                $validated[$field] = mb_strtoupper($validated[$field]);
            }
        }

        unset($validated['imagen']);

        return $validated;
    }

    private function storeImage(Request $request): string
    {
        $directory = public_path('images/derivaciones');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = $request->file('imagen');
        $filename = now()->format('Ymd_His').'_'.Str::random(12).'.'.$file->extension();
        $file->move($directory, $filename);

        return $filename;
    }

    private function deleteImage(?string $filename): void
    {
        if (! $filename) {
            return;
        }

        $path = public_path('images/derivaciones/'.$filename);
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function authorizeAny(Request $request, string|array $permissions): void
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        foreach ($permissions as $permission) {
            if ($request->user()->hasPermissionTo($permission)) {
                return;
            }
        }

        abort(403, 'No tiene permiso para realizar esta acción');
    }
}
