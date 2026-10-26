<?php

namespace App\Support;

use App\Models\MarcaRfid;
use App\Models\Matricula;
use App\Models\TarjetaRfid;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Motor determinístico de las marcas RFID (ui3/ARQUITECTURA.md): registra la pasada,
 * decide entrada/salida, propone presente/atrasado y la avisa al servidor Socket.IO
 * (proyecto socket/, POST /notify). La asistencia oficial la valida una persona.
 */
class AsistenciaRfid
{
    /** Propuesta para una entrada a esa hora: presente hasta la hora de inicio + tolerancia. */
    public static function propuesta(Carbon $marcadoEn): string
    {
        return $marcadoEn->lte(self::limite($marcadoEn)) ? 'presente' : 'atrasado';
    }

    /** Última hora de ese día que aún cuenta como presente. */
    public static function limite(Carbon $dia): Carbon
    {
        [$h, $m] = array_map('intval', explode(':', config('services.rfid.hora_inicio')));

        return $dia->copy()->setTime($h, $m)->addMinutes((int) config('services.rfid.tolerancia'));
    }

    /**
     * Registra una pasada. Si la misma tarjeta pasó hace menos de "rebote" segundos
     * (el lector la leyó dos veces) no se guarda y vuelve con duplicada = true.
     */
    public static function marcar(string $codigo, string $lector, ?Carbon $cuando = null): array
    {
        $cuando ??= now();
        $tarjeta = TarjetaRfid::where('codigo', $codigo)->where('activa', true)->with('estudiante')->first();
        $estudiante = $tarjeta?->estudiante;

        $ultima = MarcaRfid::where('codigo', $codigo)->whereDate('marcado_en', $cuando->toDateString())
            ->where('marcado_en', '<=', $cuando)->latest('marcado_en')->latest('id')->first();

        if ($ultima && $ultima->marcado_en->diffInSeconds($cuando) < (int) config('services.rfid.rebote')) {
            return ['duplicada' => true, 'codigo' => $codigo, 'nombre' => $estudiante?->nombre_completo];
        }

        $matricula = $estudiante ? Matricula::delAnio($estudiante->id, $cuando->year) : null;

        $marca = MarcaRfid::create([
            'codigo' => $codigo,
            'tarjeta_id' => $tarjeta?->id,
            'estudiante_id' => $estudiante?->id,
            'curso_id' => $matricula?->curso_id,
            'lector' => $lector,
            // Alterna: la primera del día (o la que sigue a una salida) es entrada
            'tipo' => $estudiante && $ultima?->tipo === 'entrada' ? 'salida' : 'entrada',
            'marcado_en' => $cuando,
        ]);

        $fila = self::fila($marca->load('estudiante', 'curso'));
        self::avisar($fila);

        return ['duplicada' => false] + $fila;
    }

    /** Marca tal como la muestra el panel. */
    public static function fila(MarcaRfid $m): array
    {
        return [
            'id' => $m->id,
            'codigo' => $m->codigo,
            'estudiante_id' => $m->estudiante_id,
            'nombre' => $m->estudiante?->nombre_completo,
            'curso_id' => $m->curso_id,
            'curso' => $m->curso?->nombre,
            'lector' => $m->lector,
            'tipo' => $m->tipo,
            'marcado_en' => $m->marcado_en->format('Y-m-d H:i:s'),
            'propuesta' => $m->estudiante_id && $m->tipo === 'entrada' ? self::propuesta($m->marcado_en) : null,
            'desconocida' => ! $m->estudiante_id,
        ];
    }

    /**
     * Emite la marca por el socket. Ese servidor la reemite a todos los conectados sin
     * pedir token, así que no viajan nombres ni cursos: el panel los toma de su nómina.
     * Si el socket no responde la marca queda guardada igual (el panel recarga al conectar).
     */
    public static function avisar(array $fila): void
    {
        $url = config('services.socket.url');
        if (! $url) {
            return;
        }

        try {
            Http::timeout(3)->post(rtrim($url, '/').'/notify', [
                'event' => config('services.socket.evento'),
                'data' => array_diff_key($fila, array_flip(['nombre', 'curso'])),
            ])->throw();
        } catch (\Throwable $e) {
            Log::warning('No se pudo avisar la marca RFID al socket: '.$e->getMessage());
        }
    }
}
