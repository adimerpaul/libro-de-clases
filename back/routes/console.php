<?php

use App\Models\TarjetaRfid;
use App\Support\AsistenciaRfid;
use App\Support\ImportadorCurriculo;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prueba sin lector físico: pasadas de tarjetas al azar (del año en curso), avisadas al socket
Artisan::command('rfid:simular {cantidad=10} {--cada=1 : segundos entre pasadas} {--curso= : id del curso}', function () {
    $curso = $this->option('curso');

    for ($i = 1; $i <= (int) $this->argument('cantidad'); $i++) {
        $codigo = TarjetaRfid::where('activa', true)
            ->whereHas('estudiante.matriculas', fn ($q) => $q->activas()
                ->whereHas('curso', fn ($q) => $q->where('anio', now()->year))
                ->when($curso, fn ($q, $id) => $q->where('curso_id', $id)))
            ->inRandomOrder()->value('codigo');

        if (! $codigo) {
            return $this->error('No hay estudiantes con tarjeta para simular.');
        }

        $m = AsistenciaRfid::marcar($codigo, 'Simulador');
        $this->line($m['duplicada']
            ? "{$i}. {$codigo} repetida (rebote), se ignora"
            : "{$i}. {$m['marcado_en']} {$m['tipo']} {$m['nombre']} · {$m['curso']} · ".($m['propuesta'] ?? '-'));

        sleep((int) $this->option('cada'));
    }
})->purpose('Simula pasadas de tarjetas RFID y las avisa al socket');

// Catálogo curricular desde una base SQLite "curriculum_core" (niveles, asignaturas, OA, indicadores, competencias)
Artisan::command('curriculo:importar {ruta? : archivo .sqlite (por defecto database/data/curriculum_core_demo.sqlite)}', function () {
    $ruta = $this->argument('ruta') ?? database_path('data/curriculum_core_demo.sqlite');
    foreach (ImportadorCurriculo::cargarReferencia($ruta) as $tabla => $n) {
        $this->line("{$tabla}: {$n}");
    }
    $r = ImportadorCurriculo::sincronizar();

    $this->info("Asignaturas creadas: {$r['asignaturas_creadas']} · OA creados: {$r['oa_creados']} · OA actualizados: {$r['oa_actualizados']}");
    foreach ($r['omitidos'] as $o) {
        $this->warn("Omitido {$o}");
    }
})->purpose('Carga el Módulo Curricular (SQLite curriculum_core) y sincroniza sus OA al catálogo');
