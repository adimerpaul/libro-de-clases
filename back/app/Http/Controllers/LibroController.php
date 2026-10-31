<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\LibroRespaldo;
use App\Support\LibroCurso;
use App\Support\Permisos;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Libro de clases de cada curso. Con "libros.ver" se ven todos; un docente
 * solo los cursos donde dicta alguna asignatura o es profesor(a) jefe.
 */
class LibroController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $anio = (int) ($request->anio ?: now()->year);

        $cursos = $this->visibles($request)
            ->where('anio', $anio)
            ->with(['profesorJefe:id,name', 'asignaciones' => fn ($q) => $q->with('asignatura:id,nombre,codigo,color')->select('id', 'curso_id', 'asignatura_id', 'docente_id')])
            ->withCount(['asignaciones', 'matriculas as estudiantes_count' => fn ($q) => $q->activas()])
            ->get()
            ->each(function (Curso $curso) use ($user) {
                $curso->es_jefatura = $curso->profesor_jefe_id === $user->id;
                // Asignaturas que dicta quien consulta (vacío para UTP / Dirección)
                $curso->mis_asignaturas = $curso->asignaciones->where('docente_id', $user->id)->pluck('asignatura')->values();
                $curso->unsetRelation('asignaciones');
            });

        return Curso::ordenar($cursos);
    }

    /** El libro completo: datos del curso, nómina y todas las secciones (LibroCurso). */
    public function show(Request $request, int $curso)
    {
        $curso = $this->cargar($request, $curso);
        $libro = new LibroCurso($curso, $request->user());
        $secciones = $libro->secciones();

        return $curso->toArray() + [
            'secciones' => $secciones,
            'firmas' => $libro->firmas($secciones),
            'respaldos' => LibroRespaldo::where('curso_id', $curso->id)->with('user:id,name')->latest()->latest('id')->take(5)->get()
                ->map(fn (LibroRespaldo $r) => ['folio' => $r->folio, 'hash' => $r->hash, 'usuario' => $r->user?->name, 'fecha' => $r->created_at->format('Y-m-d H:i')]),
        ];
    }

    /**
     * El libro completo en PDF: lo mismo que la vista (todas las secciones) más la hoja de
     * firmas con el código de verificación. Cada PDF queda registrado como respaldo.
     */
    public function pdf(Request $request, int $curso)
    {
        $curso = $this->cargar($request, $curso);
        $libro = new LibroCurso($curso, $request->user());
        $secciones = $libro->secciones();
        $firmas = $libro->firmas($secciones);

        $numero = LibroRespaldo::withTrashed()->where('curso_id', $curso->id)->count() + 1;
        $respaldo = LibroRespaldo::create([
            'curso_id' => $curso->id,
            'user_id' => $request->user()->id,
            'hash' => $firmas['hash'],
            'folio' => sprintf('LC-%d-%d-%03d', $curso->anio, $curso->id, $numero),
            'secciones' => array_column($secciones, 'clave'),
        ]);

        $pdf = Pdf::loadView('pdf.libro', [
            'curso' => $curso,
            'activas' => $curso->matriculas->where('estado', 'activa'),
            'tramos' => $curso->matriculas->chunk(22),
            'secciones' => $secciones,
            'firmas' => $firmas,
            'respaldo' => $respaldo,
            'establecimiento' => config('establecimiento'),
            'generado' => now()->format('d-m-Y H:i'),
            'usuario' => $request->user()->name,
        ])->setPaper('letter', 'landscape')->setOption('isFontSubsettingEnabled', true);

        return $pdf->download(Str::slug("libro {$curso->nombre} {$curso->anio} {$respaldo->folio}").'.pdf');
    }

    /** Verifica un código de respaldo impreso en un PDF firmado. */
    public function verificar(Request $request, string $hash)
    {
        $respaldo = LibroRespaldo::where('hash', $hash)->with(['curso', 'user:id,name'])->latest()->first();
        abort_unless($respaldo && $this->visibles($request)->whereKey($respaldo->curso_id)->exists(), 404, 'El código no corresponde a ningún respaldo de sus cursos.');

        return [
            'folio' => $respaldo->folio,
            'curso' => $respaldo->curso->nombre.' '.$respaldo->curso->anio,
            'generado_por' => $respaldo->user?->name,
            'fecha' => $respaldo->created_at->format('Y-m-d H:i'),
            'secciones' => $respaldo->secciones,
        ];
    }

    private function cargar(Request $request, int $curso): Curso
    {
        $curso = $this->visibles($request)
            ->with([
                'profesorJefe:id,name,email',
                'asignaciones' => fn ($q) => $q->with(['asignatura:id,nombre,codigo,color', 'docente:id,name']),
                'matriculas' => fn ($q) => $q->with('estudiante')->orderBy('numero_lista'),
            ])
            ->findOrFail($curso);

        $curso->setRelation('asignaciones', $curso->asignaciones->sortBy('asignatura.nombre')->values());

        return $curso;
    }

    /** Cursos que puede abrir quien consulta. */
    private function visibles(Request $request): Builder
    {
        return Curso::visiblesPara($request->user(), Permisos::VER_LIBROS);
    }
}
