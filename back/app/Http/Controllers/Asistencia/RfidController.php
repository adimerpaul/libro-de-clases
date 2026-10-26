<?php

namespace App\Http\Controllers\Asistencia;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\MarcaRfid;
use App\Models\Matricula;
use App\Models\TarjetaRfid;
use App\Support\AsistenciaRfid;
use Illuminate\Http\Request;

/** Panel de asistencia RFID en vivo (permiso "rfid.ver"). */
class RfidController extends Controller
{
    /** Cursos del año con estudiantes matriculados, y la hora hasta la que una entrada es "presente". */
    public function cursos()
    {
        $cursos = Curso::where('anio', now()->year)->whereHas('matriculas', fn ($q) => $q->activas())->get(['id', 'nivel', 'letra']);

        return [
            'cursos' => Curso::ordenar($cursos)->map(fn (Curso $c) => ['id' => $c->id, 'nombre' => $c->nombre]),
            'limite' => AsistenciaRfid::limite(today())->format('H:i'),
        ];
    }

    /** Nómina del curso con su tarjeta y su estado de hoy. */
    public function estudiantes(Request $request)
    {
        $request->validate(['curso_id' => 'required|integer|exists:cursos,id,deleted_at,NULL']);

        $matriculas = Matricula::activas()->where('curso_id', $request->curso_id)
            ->with('estudiante')->orderBy('numero_lista')->get()->filter->estudiante;
        $ids = $matriculas->pluck('estudiante_id');

        $tarjetas = TarjetaRfid::whereIn('estudiante_id', $ids)->where('activa', true)->pluck('codigo', 'estudiante_id');
        $hoy = MarcaRfid::whereIn('estudiante_id', $ids)->whereDate('marcado_en', today())
            ->orderBy('marcado_en')->orderBy('id')->get()->groupBy('estudiante_id');

        return $matriculas->map(function (Matricula $m) use ($tarjetas, $hoy) {
            $marcas = $hoy->get($m->estudiante_id, collect());
            $entrada = $marcas->firstWhere('tipo', 'entrada');

            return [
                'id' => $m->estudiante_id,
                'numero_lista' => $m->numero_lista,
                'nombre' => $m->estudiante->nombre_completo,
                'codigo' => $tarjetas->get($m->estudiante_id),
                'entrada' => $entrada?->marcado_en->format('Y-m-d H:i:s'),
                'dentro' => $marcas->last()?->tipo === 'entrada',
                'propuesta' => $entrada ? AsistenciaRfid::propuesta($entrada->marcado_en) : 'ausente',
            ];
        })->values();
    }

    /** Últimas marcas de hoy (todas o las del curso). */
    public function hoy(Request $request)
    {
        return MarcaRfid::whereDate('marcado_en', today())
            ->when($request->curso_id, fn ($q, $id) => $q->where('curso_id', $id))
            ->with('estudiante', 'curso')->latest('marcado_en')->latest('id')->limit(100)->get()
            ->map(fn (MarcaRfid $m) => AsistenciaRfid::fila($m));
    }

    /**
     * Pasada de una tarjeta. Sin "codigo" simula la de un estudiante al azar
     * (del curso, si viene) para probar sin lector físico.
     */
    public function marcar(Request $request)
    {
        $datos = $request->validate([
            'codigo' => 'nullable|string|max:64',
            'curso_id' => 'nullable|integer',
            'lector' => 'nullable|string|max:60',
        ]);

        $codigo = $datos['codigo'] ?? TarjetaRfid::where('activa', true)
            ->whereHas('estudiante.matriculas', fn ($q) => $q->activas()
                ->whereHas('curso', fn ($q) => $q->where('anio', now()->year))
                ->when($datos['curso_id'] ?? null, fn ($q, $id) => $q->where('curso_id', $id)))
            ->inRandomOrder()->value('codigo');

        abort_unless($codigo, 422, 'No hay estudiantes con tarjeta para simular.');

        return AsistenciaRfid::marcar($codigo, $datos['lector'] ?? 'Simulador');
    }

    /** Lo mismo por GET y sin sesión, para probar desde el navegador. Apagado fuera de local. */
    public function simular(Request $request)
    {
        abort_unless(config('services.rfid.simulador_get'), 404);

        return $this->marcar($request);
    }
}
