<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $tipoProductoId = DB::table('tipo_productos')->where('nombre', 'FARMACIA')->value('id');
        if (!$tipoProductoId) {
            $tipoProductoId = DB::table('tipo_productos')->insertGetId([
                'nombre'     => 'FARMACIA',
                'color'      => 'teal',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('productos')
            ->whereNull('tipo_producto_id')
            ->update(['tipo_producto_id' => $tipoProductoId]);
    }

    public function down(): void
    {
        // Data correction only; original null state is not meaningfully recoverable.
    }
};
