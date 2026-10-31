<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Respaldos del libro de clases: cada PDF generado queda registrado con la huella
 * SHA-256 de su contenido (código de verificación impreso en la hoja de firmas),
 * quién lo generó y cuándo. Permite comprobar que un PDF firmado no fue alterado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libro_respaldos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('hash', 64);
            $table->string('folio', 20);
            $table->json('secciones');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['curso_id', 'created_at']);
            $table->index('hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libro_respaldos');
    }
};
