<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Producto;
use App\Models\Solicitude;
use App\Models\SolicitudLaboratorioItem;
use App\Models\SolicitudLaboratorioResultado;
use App\Models\User;
use App\Models\Venta;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

class SolicitudeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAny($request, 'Ver Solicitudes Laboratorio');
        $query = Solicitude::with([
            'paciente:id,nombre_completo,ci,telefono',
            'doctor:id,nombre',
            'user:id,name',
            'laboratorioItems:id,solicitude_id,producto_nombre,precio',
        ])->latest('fecha_solicitud')->latest('id');

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($subquery) use ($q) {
                $subquery->where('codigo_solicitud', 'like', "%{$q}%")
                    ->orWhereHas('paciente', fn ($patientQuery) => $patientQuery
                        ->where('nombre_completo', 'like', "%{$q}%")
                        ->orWhere('ci', 'like', "%{$q}%"));
            });
        }
        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }
        if ($desde = $request->input('desde')) {
            $query->whereDate('fecha_solicitud', '>=', $desde);
        }
        if ($hasta = $request->input('hasta')) {
            $query->whereDate('fecha_solicitud', '<=', $hasta);
        }

        return response()->json($query->paginate((int) $request->input('per_page', 15)));
    }

    public function formData(Request $request)
    {
        $this->authorizeAny($request, ['Crear Solicitudes Laboratorio', 'Editar Solicitudes Laboratorio']);

        return response()->json([
            'doctores' => Doctor::where('estado', 'ACTIVO')->orderBy('nombre')->get(['id', 'nombre', 'registro']),
            'laboratorios' => Producto::query()
                ->whereHas('tipoProducto', fn ($query) => $query->where('es_laboratorio', true))
                ->with(['laboratorioDatos.formula', 'laboratorioDatos.opciones', 'laboratorioValidaciones' => fn ($query) => $query->where('activo', true)])
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre', 'precio', 'descripcion']),
        ]);
    }

    public function pacientes(Request $request)
    {
        $this->authorizeAny($request, ['Crear Solicitudes Laboratorio', 'Editar Solicitudes Laboratorio']);
        $query = Paciente::query()->orderBy('nombre_completo');
        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($subquery) use ($search) {
                $subquery->where('nombre_completo', 'like', "%{$search}%")
                    ->orWhere('ci', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->paginate(min((int) $request->input('per_page', 20), 50), ['id', 'nombre_completo', 'ci', 'sexo', 'fecha_nacimiento'])
        );
    }

    public function ventasLaboratorio(Request $request)
    {
        $this->authorizeAny($request, ['Crear Solicitudes Laboratorio', 'Editar Solicitudes Laboratorio']);

        $query = Venta::query()
            ->whereNotNull('paciente_id')
            ->where(function ($pagadas) {
                $pagadas->where('estado', 'ACTIVO')
                    ->orWhere(fn ($pendiente) => $pendiente
                        ->where('estado', 'PENDIENTE')
                        ->whereNotNull('fecha_hora_cobro'));
            })
            ->whereHas('detalles.producto.tipoProducto', fn ($tipo) => $tipo->where('es_laboratorio', true))
            ->with([
                'paciente:id,nombre_completo,ci,sexo,fecha_nacimiento',
                'doctor:id,nombre',
                'user:id,name',
                'cobradoPor:id,name',
                'detalles' => fn ($detalles) => $detalles
                    ->whereHas('producto.tipoProducto', fn ($tipo) => $tipo->where('es_laboratorio', true))
                    ->with('producto:id,codigo,nombre'),
            ])
            ->orderByDesc(DB::raw('COALESCE(fecha_hora_cobro, fecha_hora)'))
            ->orderByDesc('id');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($filtro) use ($search) {
                $filtro->where('id', $search)
                    ->orWhereHas('paciente', fn ($paciente) => $paciente
                        ->where('nombre_completo', 'like', "%{$search}%")
                        ->orWhere('ci', 'like', "%{$search}%"))
                    ->orWhereHas('detalles.producto', fn ($producto) => $producto
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%"));
            });
        }
        if ($fecha = $request->input('fecha')) {
            $request->validate(['fecha' => ['date']]);
            $query->whereDate(DB::raw('COALESCE(fecha_hora_cobro, fecha_hora)'), $fecha);
        }

        return response()->json($query->paginate(min((int) $request->input('per_page', 15), 50)));
    }

    public function store(Request $request)
    {
        $this->authorizeAny($request, 'Crear Solicitudes Laboratorio');
        [$validated, $productos, $valores] = $this->validatedPayload($request);

        $solicitud = DB::transaction(function () use ($validated, $productos, $request, $valores) {
            $solicitud = Solicitude::create([
                ...collect($validated)->except(['producto_ids', 'resultados'])->all(),
                'user_id' => $request->user()->id,
                'estado' => 'CREADO',
                'total' => $productos->sum('precio'),
            ]);
            $solicitud->update([
                'codigo_solicitud' => 'LAB-'.$solicitud->fecha_solicitud->format('Ymd').'-'.str_pad((string) $solicitud->id, 5, '0', STR_PAD_LEFT),
            ]);
            $this->syncLaboratorios($solicitud, $productos, $valores);

            return $solicitud;
        });

        return response()->json($solicitud->load(['paciente', 'doctor', 'laboratorioItems.resultados']), 201);
    }

    public function show(Request $request, Solicitude $solicitude)
    {
        $this->authorizeAny($request, 'Ver Solicitudes Laboratorio');

        return response()->json($solicitude->load(['paciente.seguro', 'doctor', 'user', 'laboratorioItems.resultados']));
    }

    public function update(Request $request, Solicitude $solicitude)
    {
        $this->authorizeAny($request, 'Editar Solicitudes Laboratorio');
        abort_unless($solicitude->estado === 'CREADO', 422, 'Solo se pueden modificar solicitudes en estado CREADO.');
        [$validated, $productos, $valores] = $this->validatedPayload($request);

        DB::transaction(function () use ($solicitude, $validated, $productos, $valores) {
            $solicitude->update([
                ...collect($validated)->except(['producto_ids', 'resultados'])->all(),
                'total' => $productos->sum('precio'),
            ]);
            $this->syncLaboratorios($solicitude, $productos, $valores);
        });

        return response()->json($solicitude->load(['paciente', 'doctor', 'laboratorioItems.resultados']));
    }

    public function pdf(Request $request, Solicitude $solicitude)
    {
        return $this->informe($request, $solicitude);
    }

    public function impresion(Request $request, Solicitude $solicitude)
    {
        return $this->informe($request, $solicitude, true);
    }

    private function informe(Request $request, Solicitude $solicitude, bool $html = false)
    {
        $this->authorizeAny($request, ['Ver Solicitudes Laboratorio', 'Crear Solicitudes Laboratorio', 'Editar Solicitudes Laboratorio']);
        $solicitude->load([
            'paciente',
            'doctor.especialidades:id,nombre',
            'user',
            'laboratorioItems.producto.tipoProducto:id,nombre',
            'laboratorioItems.resultados',
        ]);

        $impresoPor = $request->user();
        $urlVerificacion = rtrim(config('app.frontend_url'), '/').'/verificacion/'.$solicitude->codigo_verificacion;
        $renderer = new ImageRenderer(new RendererStyle(160, 4), new SvgImageBackEnd);
        $qrSvg = (new Writer($renderer))->writeString($urlVerificacion);
        $qrDataUri = 'data:image/svg+xml;base64,'.base64_encode($qrSvg);

        $paraNavegador = $html;
        $datos = compact(
            'solicitude', 'impresoPor', 'urlVerificacion', 'qrDataUri', 'paraNavegador'
        );
        if ($html) {
            return response()->view('reportes.solicitud-laboratorio', $datos);
        }

        $pdf = Pdf::loadView('reportes.solicitud-laboratorio', $datos)
            ->setPaper('letter');
        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(438, 772, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 7, [55 / 255, 65 / 255, 81 / 255]);

        return $pdf->stream('laboratorio_'.$solicitude->codigo_solicitud.'.pdf');
    }

    public function auditoria(Request $request, Solicitude $solicitude)
    {
        $this->authorizeAny($request, ['Ver Solicitudes Laboratorio', 'Editar Solicitudes Laboratorio']);

        $auditoriasSolicitud = Audit::query()
            ->where('auditable_type', Solicitude::class)
            ->where('auditable_id', $solicitude->id)
            ->get();

        $auditoriasItems = Audit::query()
            ->where('auditable_type', SolicitudLaboratorioItem::class)
            ->where(function ($query) use ($solicitude) {
                $query->where('new_values->solicitude_id', $solicitude->id)
                    ->orWhere('old_values->solicitude_id', $solicitude->id);
            })
            ->get();

        $itemIds = $auditoriasItems->pluck('auditable_id')->unique()->values();
        $auditoriasResultados = $itemIds->isEmpty()
            ? collect()
            : Audit::query()
                ->where('auditable_type', SolicitudLaboratorioResultado::class)
                ->where(function ($query) use ($itemIds) {
                    $query->whereIn('new_values->solicitud_laboratorio_item_id', $itemIds)
                        ->orWhereIn('old_values->solicitud_laboratorio_item_id', $itemIds);
                })
                ->get();

        $auditorias = $auditoriasSolicitud
            ->concat($auditoriasItems)
            ->concat($auditoriasResultados)
            ->sortByDesc(fn ($audit) => sprintf('%s-%010d', $audit->created_at, $audit->id))
            ->values();
        $usuarios = User::withTrashed()->whereIn('id', $auditorias->pluck('user_id')->filter()->unique())
            ->get()->keyBy('id');

        return response()->json($auditorias->map(function ($audit) use ($usuarios) {
            $anteriores = $audit->old_values ?? [];
            $nuevos = $audit->new_values ?? [];
            $valores = array_merge($anteriores, $nuevos);
            $camposIgnorados = ['id', 'solicitude_id', 'solicitud_laboratorio_item_id', 'producto_id', 'producto_laboratorio_dato_id'];
            $campos = collect(array_unique(array_merge(array_keys($anteriores), array_keys($nuevos))))
                ->reject(fn ($campo) => in_array($campo, $camposIgnorados, true))
                ->map(fn ($campo) => [
                    'campo' => $campo,
                    'anterior' => $anteriores[$campo] ?? null,
                    'nuevo' => $nuevos[$campo] ?? null,
                ])->values();

            $entidad = match ($audit->auditable_type) {
                SolicitudLaboratorioResultado::class => 'Resultado: '.($valores['nombre'] ?? '#'.$audit->auditable_id),
                SolicitudLaboratorioItem::class => 'Prueba: '.($valores['producto_nombre'] ?? '#'.$audit->auditable_id),
                default => 'Solicitud '.($valores['codigo_solicitud'] ?? '#'.$audit->auditable_id),
            };
            $usuario = $usuarios->get($audit->user_id);

            return [
                'id' => $audit->id,
                'fecha' => $audit->created_at,
                'evento' => $audit->event,
                'entidad' => $entidad,
                'usuario' => $usuario?->name ?? $usuario?->username ?? 'Usuario no registrado',
                'cambios' => $campos,
            ];
        }));
    }

    public function verificacion(string $codigo)
    {
        abort_unless(strlen($codigo) === 32, 404);

        $solicitude = Solicitude::query()
            ->where('codigo_verificacion', $codigo)
            ->with(['paciente', 'doctor', 'laboratorioItems.resultados'])
            ->firstOrFail();

        return response()->json([
            'codigo_solicitud' => $solicitude->codigo_solicitud,
            'codigo_verificacion' => $solicitude->codigo_verificacion,
            'fecha_solicitud' => $solicitude->fecha_solicitud->format('Y-m-d'),
            'hora_solicitud' => substr($solicitude->hora_solicitud, 0, 5),
            'estado' => $solicitude->estado,
            'diagnostico_clinico' => $solicitude->diagnostico_clinico,
            'observaciones' => $solicitude->observaciones,
            'paciente' => [
                'nombre_completo' => $solicitude->paciente->nombre_completo,
                'ci' => $solicitude->paciente->ci,
                'sexo' => $solicitude->paciente->sexo,
            ],
            'doctor' => $solicitude->doctor ? ['nombre' => $solicitude->doctor->nombre] : null,
            'laboratorios' => $solicitude->laboratorioItems
                ->filter(fn ($item) => $item->resultados->where('visible', true)->isNotEmpty())
                ->map(fn ($item) => [
                    'nombre' => $item->producto_nombre,
                    'resultados' => $item->resultados->where('visible', true)->values()->map(fn ($resultado) => [
                        'nombre' => $resultado->nombre,
                        'valor' => $resultado->valor,
                        'unidad' => $resultado->unidad,
                        'rango_referencia' => $resultado->rango_referencia,
                        'rango_referencia_html' => $resultado->rango_referencia_html,
                        'metodo' => $resultado->metodo,
                        'muestra' => $resultado->muestra,
                    ]),
                ])->values(),
        ]);
    }

    public function destroy(Request $request, Solicitude $solicitude)
    {
        $this->authorizeAny($request, 'Eliminar Solicitudes Laboratorio');
        abort_unless($solicitude->estado === 'CREADO', 422, 'Solo se pueden eliminar solicitudes en estado CREADO.');
        $solicitude->delete();

        return response()->json(['message' => 'Solicitud eliminada']);
    }

    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'paciente_id' => ['required', 'exists:pacientes,id'],
            'doctor_id' => ['nullable', 'exists:doctores,id'],
            'fecha_solicitud' => ['required', 'date'],
            'hora_solicitud' => ['required', 'date_format:H:i'],
            'diagnostico_clinico' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'producto_ids' => ['required', 'array', 'min:1'],
            'producto_ids.*' => ['required', 'integer', 'distinct', 'exists:productos,id'],
            'resultados' => ['nullable', 'array'],
            'resultados.*.producto_laboratorio_dato_id' => ['required', 'integer'],
            'resultados.*.valor' => ['nullable', 'string', 'max:2000'],
            'resultados.*.visible' => ['nullable', 'boolean'],
        ]);
        $productos = Producto::query()
            ->whereIn('id', $validated['producto_ids'])
            ->whereHas('tipoProducto', fn ($query) => $query->where('es_laboratorio', true))
            ->with(['laboratorioDatos.formula', 'laboratorioDatos.opciones'])
            ->get()
            ->sortBy(fn ($producto) => array_search($producto->id, $validated['producto_ids'], true))
            ->values();
        abort_unless($productos->count() === count($validated['producto_ids']), 422, 'Seleccione únicamente productos de laboratorio.');

        return [
            $validated,
            $productos,
            collect($validated['resultados'] ?? [])->keyBy('producto_laboratorio_dato_id'),
        ];
    }

    private function syncLaboratorios(Solicitude $solicitud, $productos, $valores): void
    {
        $itemsAnteriores = $solicitud->laboratorioItems()->with('resultados')->get()->keyBy('producto_id');

        foreach ($productos as $indice => $producto) {
            $datosItem = [
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
                'precio' => $producto->precio,
                'orden' => ($indice + 1) * 10,
            ];
            $item = $itemsAnteriores->pull($producto->id);
            if ($item) {
                $item->update($datosItem);
            } else {
                $item = $solicitud->laboratorioItems()->create($datosItem);
            }
            $resultadosAnteriores = $item->resultados->keyBy('producto_laboratorio_dato_id');

            foreach ($producto->laboratorioDatos as $dato) {
                $valorConfigurado = $valores->get($dato->id, []);
                $datosResultado = [
                    'producto_laboratorio_dato_id' => $dato->id,
                    'nombre' => $dato->nombre,
                    'nombre_variable' => $dato->nombre_variable,
                    'unidad' => $dato->unidad,
                    'metodo' => $dato->metodo,
                    'muestra' => $dato->muestra,
                    'rango_referencia' => $dato->rango_referencia,
                    'formula' => $dato->formula?->formula,
                    'valor' => $valorConfigurado['valor'] ?? null,
                    'orden' => $dato->orden,
                    'visible' => array_key_exists('visible', $valorConfigurado)
                        ? (bool) $valorConfigurado['visible']
                        : $dato->visible,
                ];
                $resultado = $resultadosAnteriores->pull($dato->id);
                $resultado ? $resultado->update($datosResultado) : $item->resultados()->create($datosResultado);
            }
            $resultadosAnteriores->each->delete();
        }

        $itemsAnteriores->each(function ($item) {
            $item->resultados->each->delete();
            $item->delete();
        });
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
