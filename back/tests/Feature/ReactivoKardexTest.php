<?php

use App\Models\Reactivo;
use App\Services\ReactivoKardex;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    app('db')->purge('sqlite');
    $tables = [
        'reactivos' => ['nombre', 'codigo', 'unidad'],
        'servicio_laboratorio_reactivos' => ['reactivo_id', 'producto_id', 'cantidad'],
        'solicitudes' => ['fecha_solicitud', 'estado', 'user_id'],
        'solicitud_laboratorio_items' => ['solicitude_id', 'producto_id', 'producto_nombre', 'orden'],
        'solicitud_laboratorio_resultados' => ['solicitud_laboratorio_item_id', 'valor', 'orden'],
        'users' => ['name'],
    ];
    foreach ($tables as $nombre => $campos) {
        Schema::create($nombre, function (Blueprint $table) use ($campos) {
            $table->id();
            foreach ($campos as $campo) {
                $table->string($campo)->nullable();
            }
            $table->timestamps();
            $table->softDeletes();
        });
    }
});

it('calcula el consumo del mes sin duplicar pruebas con varios resultados', function () {
    $reactivo = Reactivo::create(['nombre' => 'GOT', 'unidad' => 'ML']);
    DB::table('servicio_laboratorio_reactivos')->insert(['reactivo_id' => $reactivo->id, 'producto_id' => 10, 'cantidad' => 0.25]);
    DB::table('users')->insert(['id' => 1, 'name' => 'ARACELY']);
    foreach ([
        [1, '2026-08-01', 'FINALIZADO', 10, null],
        [2, '2026-08-31', 'CREADO', 10, '0'],
        [3, '2026-09-01', 'FINALIZADO', 10, null],
        [4, '2026-08-02', 'ANULADO', 10, '5'],
        [5, '2026-08-03', 'CREADO', 10, null],
        [6, '2026-08-04', 'ANALIZADO', 99, null],
        [7, '2026-08-05', 'CREADO', 10, '   '],
    ] as [$id, $fecha, $estado, $producto, $valor]) {
        DB::table('solicitudes')->insert(['id' => $id, 'fecha_solicitud' => $fecha, 'estado' => $estado, 'user_id' => 1]);
        DB::table('solicitud_laboratorio_items')->insert(['id' => $id, 'solicitude_id' => $id, 'producto_id' => $producto, 'producto_nombre' => 'GOT']);
        if ($valor !== null) {
            foreach (range(1, 2) as $orden) {
                DB::table('solicitud_laboratorio_resultados')->insert(['solicitud_laboratorio_item_id' => $id, 'valor' => $valor, 'orden' => $orden]);
            }
        }
    }
    $datos = ReactivoKardex::datos($reactivo, '2026-08');
    expect($datos['cantidad_pruebas'])->toBe(2)
        ->and($datos['total_salidas'])->toBe(0.5)
        ->and($datos['saldo_inicial'])->toBeNull()
        ->and($datos['saldo_final'])->toBeNull()
        ->and($datos['movimientos'])->toHaveCount(2)
        ->and($datos['movimientos'][0]['responsable'])->toBe('ARACELY');
    DB::table('solicitud_laboratorio_items')->where('id', 1)->update(['deleted_at' => now()]);
    expect(ReactivoKardex::datos($reactivo, '2026-08')['cantidad_pruebas'])->toBe(1);
});

it('devuelve un reporte vacio cuando el reactivo no tiene servicios vinculados', function () {
    $reactivo = Reactivo::create(['nombre' => 'SIN VINCULOS', 'unidad' => 'ML']);
    $datos = ReactivoKardex::datos($reactivo, '2026-08');
    expect($datos['cantidad_pruebas'])->toBe(0)
        ->and($datos['total_salidas'])->toBe(0.0)
        ->and($datos['movimientos'])->toHaveCount(0);
});
