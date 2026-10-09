<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('productos')
            ->where(function ($query) {
                $query->whereNull('tipo')
                    ->orWhere('tipo', '!=', 'FARMACIA');
            })
            ->update([
                'tipo' => 'FARMACIA',
            ]);
    }

    public function down(): void
    {
        // Data correction only; original migrated types are not recoverable.
    }
};

