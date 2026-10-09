<?php

namespace Database\Seeders;

use App\Models\Producto;
use App\Models\TipoProducto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rellena `nombre_comercial` de los productos de farmacia.
 *
 * En el catálogo el campo `nombre` es el nombre genérico (principio activo) o,
 * cuando el producto se compró por marca, ya el nombre de fábrica. El seeder:
 *
 *  1. Si el nombre empieza con un principio activo conocido, usa la marca
 *     comercial con la que ese principio se vende (tabla MARCAS).
 *  2. Si es un insumo o equipo (gasas, jeringas, sondas…), lo deja vacío:
 *     esos productos no tienen nombre comercial, su marca va en `marca`.
 *  3. En el resto el propio `nombre` ya es el nombre comercial, así que se copia.
 *
 * Nunca pisa un valor existente, de modo que se puede volver a ejecutar sin
 * deshacer lo que farmacia haya corregido a mano.
 */
class ProductoNombreComercialSeeder extends Seeder
{
    /** Principio activo (prefijo del nombre) => nombre comercial más usado. */
    private const MARCAS = [
        'ACETILCISTEINA' => 'FLUIMUCIL',
        'ACICLOVIR' => 'ZOVIRAX',
        'ALBENDAZOL' => 'ZENTEL',
        'ALBUMINA HUMANA' => 'ALBUTEIN',
        'AMIODARONA' => 'CORDARONE',
        'AMIODORONA' => 'CORDARONE',
        'AMITRIPTILINA' => 'TRYPTANOL',
        'AMLODIPINA' => 'NORVASC',
        'AMOXICILINA' => 'AMOVAL',
        'AMPICILINA' => 'PENTREXYL',
        'ASA' => 'ASPIRINA',
        'ATORVASTATINA' => 'LIPITOR',
        'ATRACURIO' => 'TRACRIUM',
        'ATROPINA' => 'ATROPINA SULFATO',
        'AZITROMICINA' => 'ZITROMAX',
        'BACITRACINA NEOMICINA' => 'NEOSPORIN',
        'BUPIVACAINA' => 'MARCAINA',
        'BUTIL BROMURO HIOSCINA' => 'BUSCAPINA',
        'CARBAMAZEPINA' => 'TEGRETOL',
        'CARVEDILOL' => 'DILATREND',
        'CEFALEXINA' => 'KEFLEX',
        'CEFIXIMA' => 'DENVAR',
        'CEFOTAXIM' => 'CLAFORAN',
        'CEFOTAXIMA' => 'CLAFORAN',
        'CEFTADIZIMA' => 'FORTUM',
        'CEFTAZIDIMA' => 'FORTUM',
        'CEFTRIAXON' => 'ROCEPHIN',
        'CEFTRIAXONA' => 'ROCEPHIN',
        'CIPROFLOXACINA' => 'CIPROXINA',
        'CLINDAMICINA' => 'DALACIN',
        'CLOPIDOGREL' => 'PLAVIX',
        'CLORANFENICOL' => 'CHLOROMYCETIN',
        'CLORFENAMINA' => 'CLORTRIMETON',
        'CLORFERINAMINA' => 'CLORTRIMETON',
        'CODEINA' => 'CODIPRONT',
        'COTRIMOXAZOL' => 'BACTRIM',
        'DEXAMETASONA' => 'DECADRON',
        'DEXMEDETOMIDINA' => 'PRECEDEX',
        'DEXTROMETORFAN' => 'ROBITUSSIN',
        'DEXTROMETORFAN O' => 'ROBITUSSIN',
        'DICLOFENACO' => 'VOLTAREN',
        'DICLOXACILINA' => 'DICLOCIL',
        'DIGOXINA' => 'LANOXIN',
        'DOBUTAMINA' => 'DOBUTREX',
        'ENOXAPARINA' => 'CLEXANE',
        'ETILEFRINA CLORHIDRATO' => 'EFORTIL',
        'FENITOINA' => 'EPAMIN',
        'FENTANILO' => 'FENTANEST',
        'FLUOXETINA' => 'PROZAC',
        'FUROSEMIDA' => 'LASIX',
        'GENTAMICINA' => 'GARAMICINA',
        'HEPARINA SODICA' => 'HEPARIN SODICO',
        'HIDROCORTISONA' => 'SOLU CORTEF',
        'HIDROCORTIZONA' => 'SOLU CORTEF',
        'IBUPROFENO' => 'ADVIL',
        'IMIPENEM CILASTATINA' => 'TIENAM',
        'IVERMECTINA' => 'IVEXTERM',
        'KETOPROFENO' => 'PROFENID',
        'LEVOFLOXACINA' => 'TAVANIC',
        'LIDOCAINA' => 'XYLOCAINA',
        'LOSARTAN' => 'COZAAR',
        'MANITOL' => 'OSMITROL',
        'MELOXICAM' => 'MOBIC',
        'MEROPENEM' => 'MERONEM',
        'METOCLOPRAMIDA' => 'PRIMPERAN',
        'MIDAZOLAM' => 'DORMICUM',
        'MORFINA' => 'MORFINA CLORHIDRATO',
        'NALOXONA' => 'NARCAN',
        'NEOSTIGMINA' => 'PROSTIGMINE',
        'NISTATINA' => 'MICOSTATIN',
        'NORADRENALINA' => 'LEVOPHED',
        'OMEPRAZOL' => 'LOSEC',
        'ONDANSETRON' => 'ZOFRAN',
        'OXITOCINA' => 'SYNTOCINON',
        'PARACETAMOL' => 'PANADOL',
        'PIPERACICLINA MAS TAZOBACTAM' => 'TAZONAM',
        'PREDNISONA' => 'METICORTEN',
        'PREGABALINA' => 'LYRICA',
        'PROPOFOL' => 'DIPRIVAN',
        'QUETIAPINA' => 'SEROQUEL',
        'RIFAMICINA' => 'OTOFA',
        'SALBUTAMOL' => 'VENTOLIN',
        'SEVOFLURANO' => 'SEVORANE',
        'VANCOMICINA' => 'VANCOCIN',
        'VITAMINA C' => 'REDOXON',
        'VITAMINA K' => 'KONAKION',
    ];

    /** Insumos y equipos: se reconocen por el código o por estas palabras. */
    private const INSUMOS = [
        'AGUJA', 'ALGODON', 'BAJALENGUA', 'BISTURI', 'BOLSA', 'BRANULA', 'CANULA',
        'CEPILLO', 'CLAMP', 'CLIP', 'COLLARIN', 'CONECTOR', 'CUBRE', 'CUBREZAPATOS',
        'ELECTRODO', 'ELECTRODOS', 'EQUIPO', 'ESPARADRAPO', 'ESPATULA', 'ESPECULO',
        'ESPONJA', 'FILTRO', 'FRASCO', 'GASA', 'GLUCOCINTAS', 'GORROS', 'GUANTES',
        'HEMOVAC', 'HILO', 'JERINGA', 'LAPIZ', 'LLAVE DE', 'MICROGOTERO', 'MICROPORE',
        'PAPEL', 'PORTAOBJETO', 'PRESERVATIVO', 'SONDA', 'SONDA NASOGATRICA',
        'TERMOMETRO', 'TESTIGO', 'TUBO', 'VENDA', 'VENDAS',
    ];

    public function run(): void
    {
        $tipoId = TipoProducto::where('nombre', 'FARMACIA')->value('id');
        if (! $tipoId) {
            $this->command?->warn('No existe el tipo de producto FARMACIA; nada que rellenar.');

            return;
        }

        $porMarca = 0;
        $porNombre = 0;
        $sinDato = 0;

        Producto::where('tipo_producto_id', $tipoId)
            ->whereNull('nombre_comercial')
            ->select(['id', 'codigo', 'nombre'])
            ->chunkById(200, function ($productos) use (&$porMarca, &$porNombre, &$sinDato) {
                foreach ($productos as $producto) {
                    $comercial = $this->comercialDe($producto->nombre);

                    if ($comercial !== null) {
                        $porMarca++;
                    } elseif ($this->esInsumo($producto->codigo, $producto->nombre)) {
                        $sinDato++;

                        continue;
                    } else {
                        // El nombre ya es de fábrica (ABRILAR, ACTRON, DIOXADOL…).
                        $comercial = mb_strtoupper($producto->nombre);
                        $porNombre++;
                    }

                    // Actualización directa: son cientos de filas de carga inicial
                    // y no aportan nada a la auditoría de cambios del usuario.
                    DB::table('productos')
                        ->where('id', $producto->id)
                        ->update(['nombre_comercial' => $comercial]);
                }
            });

        $this->command?->info("Nombres comerciales: {$porMarca} por principio activo, {$porNombre} copiados del nombre, {$sinDato} insumos sin nombre comercial.");
    }

    /** Marca comercial del principio activo con el que empieza el nombre. */
    private function comercialDe(string $nombre): ?string
    {
        $nombre = mb_strtoupper(trim($nombre));

        foreach (self::MARCAS as $generico => $marca) {
            // Coincidencia por palabra completa: AMOXICILINA 500 sí, AMOXIDIN no.
            if ($nombre === $generico || str_starts_with($nombre, $generico.' ')) {
                return $marca;
            }
        }

        return null;
    }

    private function esInsumo(?string $codigo, string $nombre): bool
    {
        $codigo = mb_strtoupper((string) $codigo);
        if (str_starts_with($codigo, 'INSU') || str_starts_with($codigo, 'EQUI')) {
            return true;
        }

        $nombre = mb_strtoupper(trim($nombre));
        foreach (self::INSUMOS as $palabra) {
            if ($nombre === $palabra || str_starts_with($nombre, $palabra.' ')) {
                return true;
            }
        }

        return false;
    }
}
