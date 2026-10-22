<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstrumentoPregunta extends Model
{
    use SoftDeletes;

    public const TIPOS = ['seleccion', 'verdadero_falso', 'desarrollo'];

    protected $table = 'instrumento_preguntas';

    protected $fillable = [
        'instrumento_id', 'orden', 'tipo', 'enunciado', 'alternativas', 'respuesta_correcta', 'criterios', 'puntaje',
    ];

    protected function casts(): array
    {
        return [
            'alternativas' => 'array',
            'puntaje' => 'float',
            'orden' => 'integer',
        ];
    }

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(Instrumento::class);
    }
}
