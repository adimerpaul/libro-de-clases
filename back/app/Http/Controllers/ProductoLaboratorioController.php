<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoLaboratorioDato;
use App\Models\ProductoLaboratorioFormula;
use App\Models\ProductoLaboratorioValidacion;
use App\Support\DescripcionHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductoLaboratorioController extends Controller
{
    public function show(Request $request, Producto $producto)
    {
        $this->authorizeAny($request, ['Ver Productos', 'Editar Productos']);
        $this->ensureLaboratorio($producto);

        return response()->json($producto->load([
            'laboratorioDatos.formula',
            'laboratorioDatos.opciones',
            'laboratorioFormulas',
            'laboratorioValidaciones',
        ]));
    }

    public function storeDato(Request $request, Producto $producto)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($producto);
        $data = $this->validateDato($request, $producto);
        $opciones = $data['opciones'];
        unset($data['opciones']);
        $data['producto_id'] = $producto->id;
        $data['orden'] = ((int) $producto->laboratorioDatos()->max('orden')) + 10;

        $dato = ProductoLaboratorioDato::create($data);
        $this->sincronizarOpciones($dato, $opciones);

        return response()->json($dato->load('opciones'), 201);
    }

    public function updateDato(Request $request, ProductoLaboratorioDato $dato)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($dato->producto);
        $data = $this->validateDato($request, $dato->producto, $dato);
        $opciones = $data['opciones'];
        unset($data['opciones']);

        $dato->update($data);
        $this->sincronizarOpciones($dato, $opciones);
        $dato->formula?->update([
            'nombre' => $dato->nombre,
            'nombre_variable' => $dato->nombre_variable,
            'unidad' => $dato->unidad,
            'visible' => $dato->visible,
        ]);

        return response()->json($dato->load('opciones'));
    }

    public function destroyDato(Request $request, ProductoLaboratorioDato $dato)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($dato->producto);

        $usada = $dato->producto->laboratorioFormulas()
            ->where('formula', 'regexp', '(^|[^a-zA-Z0-9_])'.$dato->nombre_variable.'([^a-zA-Z0-9_]|$)')
            ->exists();

        if ($usada) {
            return response()->json([
                'message' => 'No se puede eliminar: la variable está utilizada en una fórmula.',
            ], 422);
        }

        $dato->formula?->delete();
        $dato->delete();

        return response()->json(['message' => 'Dato eliminado']);
    }

    public function storeDatoFormula(Request $request, ProductoLaboratorioDato $dato)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($dato->producto);
        $data = $this->validateDatoFormula($request, $dato);

        $formula = $dato->formula()->withTrashed()->first() ?? new ProductoLaboratorioFormula;
        $formula->fill([
            ...$data,
            'producto_id' => $dato->producto_id,
            'producto_laboratorio_dato_id' => $dato->id,
            'nombre' => $dato->nombre,
            'nombre_variable' => $dato->nombre_variable,
            'unidad' => $dato->unidad,
            'orden' => $dato->orden,
            'visible' => $dato->visible,
        ]);
        $formula->deleted_at = null;
        $formula->save();

        return response()->json($formula, 201);
    }

    public function reorderDatos(Request $request, Producto $producto)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($producto);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $currentIds = $producto->laboratorioDatos()->pluck('id')->sort()->values();
        $requestedIds = collect($validated['ids'])->sort()->values();

        if ($currentIds->all() !== $requestedIds->all()) {
            throw ValidationException::withMessages([
                'ids' => 'La lista de datos no corresponde al laboratorio seleccionado.',
            ]);
        }

        DB::transaction(function () use ($validated, $producto) {
            foreach ($validated['ids'] as $index => $id) {
                $producto->laboratorioDatos()->whereKey($id)->update([
                    'orden' => ($index + 1) * 10,
                ]);
            }
        });

        return response()->json(['message' => 'Orden actualizado']);
    }

    public function storeFormula(Request $request, Producto $producto)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($producto);
        $data = $this->validateFormula($request, $producto);
        $data['producto_id'] = $producto->id;
        $data['orden'] = ((int) $producto->laboratorioFormulas()->max('orden')) + 10;

        return response()->json(ProductoLaboratorioFormula::create($data), 201);
    }

    public function updateFormula(Request $request, ProductoLaboratorioFormula $formula)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($formula->producto);
        if ($formula->dato) {
            $formula->update($this->validateDatoFormula($request, $formula->dato));
        } else {
            $formula->update($this->validateFormula($request, $formula->producto, $formula));
        }

        return response()->json($formula);
    }

    public function destroyFormula(Request $request, ProductoLaboratorioFormula $formula)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($formula->producto);
        $formula->delete();

        return response()->json(['message' => 'Fórmula eliminada']);
    }

    /**
     * Deja la lista de opciones del dato igual a la recibida, conservando las
     * filas que ya existían para no perder su historial de auditoría.
     */
    private function sincronizarOpciones(ProductoLaboratorioDato $dato, array $valores): void
    {
        $existentes = $dato->opciones()->get()->keyBy('valor');

        foreach ($valores as $indice => $valor) {
            $opcion = $existentes->get($valor);
            if ($opcion) {
                $opcion->update(['orden' => $indice]);
                $existentes->forget($valor);
            } else {
                $dato->opciones()->create(['valor' => $valor, 'orden' => $indice]);
            }
        }

        // Lo que quedó en $existentes ya no está en la lista enviada.
        $existentes->each->delete();
    }

    private function validateDato(
        Request $request,
        Producto $producto,
        ?ProductoLaboratorioDato $dato = null
    ): array {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'nombre_variable' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('producto_laboratorio_datos', 'nombre_variable')
                    ->where('producto_id', $producto->id)
                    ->whereNull('deleted_at')
                    ->ignore($dato?->id),
            ],
            'unidad' => 'nullable|string|max:100',
            'metodo' => 'nullable|string|max:100',
            'muestra' => 'nullable|string|max:100',
            'rango_referencia' => 'nullable|string|max:1000000',
            'valor_defecto' => 'nullable|string|max:255',
            'opciones' => 'nullable|array|max:50',
            'opciones.*' => 'required|string|max:255',
        ]);

        $data['nombre'] = mb_strtoupper(trim($data['nombre']));
        $data['nombre_variable'] = mb_strtolower(trim($data['nombre_variable']));
        $data['unidad'] = isset($data['unidad']) ? trim($data['unidad']) : null;
        $data['metodo'] = isset($data['metodo']) && trim($data['metodo']) !== ''
            ? mb_strtoupper(trim($data['metodo']))
            : null;
        $data['muestra'] = isset($data['muestra']) && trim($data['muestra']) !== ''
            ? mb_strtoupper(trim($data['muestra']))
            : null;
        // El rango llega como HTML del editor: se limpia y, si no tiene texto
        // (el editor deja "<br>" o "<p></p>"), se guarda como nulo.
        $rango = trim($data['rango_referencia'] ?? '');
        $textoRango = str_replace("\u{00A0}", ' ', html_entity_decode(strip_tags($rango), ENT_QUOTES | ENT_HTML5));
        $data['rango_referencia'] = trim($textoRango) !== '' ? DescripcionHtml::guardar($rango) : null;
        $data['valor_defecto'] = isset($data['valor_defecto']) && trim($data['valor_defecto']) !== ''
            ? mb_strtoupper(trim($data['valor_defecto']))
            : null;

        // Las opciones se normalizan y se quitan duplicados y vacíos.
        $data['opciones'] = collect($data['opciones'] ?? [])
            ->map(fn ($valor) => mb_strtoupper(trim($valor)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Si el dato tiene lista cerrada, el valor por defecto debe salir de
        // ella: si no, el resultado arrancaría con un valor que el operador no
        // puede volver a elegir.
        if ($data['opciones'] && $data['valor_defecto'] && ! in_array($data['valor_defecto'], $data['opciones'], true)) {
            throw ValidationException::withMessages([
                'valor_defecto' => 'El valor por defecto debe ser una de las opciones de la lista.',
            ]);
        }

        if ($producto->laboratorioFormulas()
            ->when($dato, fn ($query) => $query->where('producto_laboratorio_dato_id', '!=', $dato->id))
            ->where('nombre_variable', $data['nombre_variable'])
            ->exists()) {
            throw ValidationException::withMessages([
                'nombre_variable' => 'La variable ya está utilizada como resultado de una fórmula.',
            ]);
        }

        return $data;
    }

    private function validateFormula(
        Request $request,
        Producto $producto,
        ?ProductoLaboratorioFormula $formula = null
    ): array {
        $data = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'nombre_variable' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('producto_laboratorio_formulas', 'nombre_variable')
                    ->where('producto_id', $producto->id)
                    ->whereNull('deleted_at')
                    ->ignore($formula?->id),
            ],
            'formula' => ['required', 'string', 'max:2000', 'regex:/^[a-zA-Z0-9_+\-*\/().\s]+$/'],
            'unidad' => 'nullable|string|max:100',
            'visible' => 'required|boolean',
        ]);

        $variables = $producto->laboratorioDatos()->pluck('nombre_variable')
            ->merge(
                $producto->laboratorioFormulas()
                    ->when($formula, fn ($query) => $query->whereKeyNot($formula->id))
                    ->pluck('nombre_variable')
            )
            ->unique();

        preg_match_all('/\b[a-zA-Z][a-zA-Z0-9_]*\b/', $data['formula'], $matches);
        $unknown = collect($matches[0])->map(fn ($value) => mb_strtolower($value))
            ->diff($variables)
            ->unique()
            ->values();

        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'formula' => 'Variables desconocidas: '.$unknown->join(', '),
            ]);
        }

        $data['nombre'] = isset($data['nombre']) ? mb_strtoupper(trim($data['nombre'])) : null;
        $data['nombre_variable'] = mb_strtolower(trim($data['nombre_variable']));
        $data['formula'] = mb_strtolower(trim($data['formula']));
        $data['unidad'] = isset($data['unidad']) ? trim($data['unidad']) : null;

        if ($producto->laboratorioDatos()->where('nombre_variable', $data['nombre_variable'])->exists()) {
            throw ValidationException::withMessages([
                'nombre_variable' => 'La variable ya está utilizada por un dato del laboratorio.',
            ]);
        }

        return $data;
    }

    private function validateDatoFormula(Request $request, ProductoLaboratorioDato $dato): array
    {
        $data = $request->validate([
            'formula' => ['required', 'string', 'max:2000', 'regex:/^[a-zA-Z0-9_+\-*\/().\s]+$/'],
        ]);

        $variables = $dato->producto->laboratorioDatos()
            ->whereKeyNot($dato->id)
            ->pluck('nombre_variable');

        preg_match_all('/\b[a-zA-Z][a-zA-Z0-9_]*\b/', $data['formula'], $matches);
        $unknown = collect($matches[0])
            ->map(fn ($value) => mb_strtolower($value))
            ->diff($variables)
            ->unique()
            ->values();

        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'formula' => 'Variables desconocidas: '.$unknown->join(', '),
            ]);
        }

        $data['formula'] = mb_strtolower(trim($data['formula']));

        return $data;
    }

    // ── Validaciones (mensajes de control) ────────────────────────

    public function storeValidacion(Request $request, Producto $producto)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($producto);
        $data = $this->validateValidacion($request, $producto);
        $data['producto_id'] = $producto->id;
        $data['orden'] = ((int) $producto->laboratorioValidaciones()->max('orden')) + 10;

        return response()->json(ProductoLaboratorioValidacion::create($data), 201);
    }

    public function updateValidacion(Request $request, ProductoLaboratorioValidacion $validacion)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($validacion->producto);
        $validacion->update($this->validateValidacion($request, $validacion->producto));

        return response()->json($validacion);
    }

    public function destroyValidacion(Request $request, ProductoLaboratorioValidacion $validacion)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($validacion->producto);
        $validacion->delete();

        return response()->json(['message' => 'Mensaje eliminado']);
    }

    private function validateValidacion(Request $request, Producto $producto): array
    {
        $data = $request->validate([
            'expresion' => ['required', 'string', 'max:2000', 'regex:/^[a-zA-Z0-9_+\-*\/().\s]+$/'],
            'operador' => ['required', Rule::in(['=', '!=', '>', '>=', '<', '<=', 'entre'])],
            'valor' => ['required', 'numeric'],
            'valor_hasta' => ['nullable', 'numeric', 'required_if:operador,entre'],
            'mensaje' => ['required', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ]);

        $variables = $producto->laboratorioDatos()->pluck('nombre_variable');
        preg_match_all('/\b[a-zA-Z][a-zA-Z0-9_]*\b/', $data['expresion'], $matches);
        $unknown = collect($matches[0])
            ->map(fn ($value) => mb_strtolower($value))
            ->diff($variables)
            ->unique()
            ->values();

        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'expresion' => 'Variables desconocidas: '.$unknown->join(', '),
            ]);
        }

        if ($data['operador'] === 'entre' && (float) $data['valor_hasta'] < (float) $data['valor']) {
            throw ValidationException::withMessages([
                'valor_hasta' => 'El valor final debe ser mayor o igual al inicial.',
            ]);
        }

        $data['expresion'] = mb_strtolower(trim($data['expresion']));
        if ($data['operador'] !== 'entre') {
            $data['valor_hasta'] = null;
        }

        return $data;
    }

    private function ensureLaboratorio(Producto $producto): void
    {
        // Cualquier área marcada como laboratorio sirve (HEMATOLOGIA,
        // UROANALISIS, …), no solo el tipo genérico LABORATORIOS.
        abort_unless(
            $producto->tipoProducto()->where('es_laboratorio', true)->exists(),
            422,
            'El producto seleccionado no pertenece a un área de laboratorio.'
        );
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
