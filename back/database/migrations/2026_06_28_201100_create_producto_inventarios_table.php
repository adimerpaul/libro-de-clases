<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_inventarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('responsable')->nullable();
            $table->string('lote')->nullable();
            $table->decimal('cantidad_principal', 12, 2)->nullable();
            $table->foreignId('unidad_principal_id')->nullable()->constrained('unidades')->nullOnDelete();
            $table->decimal('cantidad_secundaria', 12, 2)->nullable();
            $table->foreignId('unidad_secundaria_id')->nullable()->constrained('unidades')->nullOnDelete();
            $table->string('origen_archivo')->nullable();
            $table->timestamps();

            $table->index(['producto_id', 'lote']);
            $table->index('responsable');
        });

        $inventarios = json_decode(<<<'JSON'
[
  {
    "codigo": "MEDISLCH3",
    "nombre": "DIGOXINA LCH",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "0224 FDTI",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDA",
    "nombre": "NALOXONA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "40789",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDH",
    "nombre": "AZITROMICINA 1",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "84161",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "INSU104",
    "nombre": "VENDA DE GASA 10 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20125",
    "cantidad_principal": 0,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU104",
    "nombre": "VENDA DE GASA 10 CM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20250325",
    "cantidad_principal": 1.1,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 13,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU104",
    "nombre": "VENDA DE GASA 10 CM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "7276",
    "cantidad_principal": 1.5,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 18,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU112",
    "nombre": "VENDA DE YESO 10 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "774262421",
    "cantidad_principal": 1.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 34,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDIF",
    "nombre": "AMLODIPINA 10",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "42519",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 15,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "EQUI7826",
    "nombre": "JERINGA 10 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "12345",
    "cantidad_principal": 4.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 463,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU127",
    "nombre": "TEGADERM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "52419HC",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "ESPONJA HEMOSTATICA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2503",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "MED",
    "nombre": "LEUKOMED T",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "40942822",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDH",
    "nombre": "DEXMEDETOMIDINA 100MCG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "992629",
    "cantidad_principal": 1.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDA",
    "nombre": "FENITOINA 100AMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "50238",
    "cantidad_principal": 1.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI9",
    "nombre": "DOLOGRIP INFANTIL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "725106",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI11",
    "nombre": "TUSSINOL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "72577",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDISLCH2",
    "nombre": "CARVEDILOL 12,5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "0723 EVMU",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 23,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDA",
    "nombre": "DOBUTAMINA 12.5 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24153",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "INSU109",
    "nombre": "VENDA ELASTICA 15 CM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "11111",
    "cantidad_principal": 1.7,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU109",
    "nombre": "VENDA ELASTICA 15 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20231218",
    "cantidad_principal": 0.1,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU109",
    "nombre": "VENDA ELASTICA 15 CM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20231218",
    "cantidad_principal": 0.5,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU113",
    "nombre": "VENDA DE YESO 15 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "79826234",
    "cantidad_principal": 2.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 65,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDIF",
    "nombre": "MELOXICAM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "62426",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDA",
    "nombre": "AMIODORONA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "16048",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MED",
    "nombre": "BICERTO 150",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "977980",
    "cantidad_principal": 1.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 17,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "INSU62",
    "nombre": "MICROGOTERO 150 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20241016",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU62",
    "nombre": "MICROGOTERO 150 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20241016",
    "cantidad_principal": 2.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 25,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU62",
    "nombre": "MICROGOTERO 150 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20250616",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU62",
    "nombre": "MICROGOTERO 150 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20250616",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDI7827",
    "nombre": "MELOXICAM 15MG",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "C105191",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 488,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "AYV10",
    "nombre": "BUTIL BROMURO HIOSCINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "28000",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 29,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "INSU110",
    "nombre": "VENDA ELASTICA 20 CM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "11111",
    "cantidad_principal": 1.8,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU110",
    "nombre": "VENDA ELASTICA 20 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20230820",
    "cantidad_principal": 0.4,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU114",
    "nombre": "VENDA DE YESO 20 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "604262447",
    "cantidad_principal": 2.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 48,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "ATORVASTATINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "240203",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDISLCH7",
    "nombre": "FLUOXETINA LCH",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "E0624",
    "cantidad_principal": 1.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDISLCH5",
    "nombre": "PREDNISONA LCH",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "E0725 33548",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "INSU57",
    "nombre": "JERINGA 20 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20221030",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU57",
    "nombre": "JERINGA 20 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20221030",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU57",
    "nombre": "JERINGA 20 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20240620",
    "cantidad_principal": 10.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 531,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDT",
    "nombre": "ENTEROCOLIN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "NFR2072301",
    "cantidad_principal": 2.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 92,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDISLCH1",
    "nombre": "CARBAMAZEPINA LCH",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "33102",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 77,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDISLCH1",
    "nombre": "CARBAMAZEPINA LCH",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "E0524",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "INSU22",
    "nombre": "BOLSA DE ORINA NIPRO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "052023AZC01",
    "cantidad_principal": 5,
    "unidad_principal": "SOBRE PLASTICO",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "BOLSA"
  },
  {
    "codigo": "INSU22",
    "nombre": "BOLSA DE ORINA NIPRO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "062022A2C01",
    "cantidad_principal": 0,
    "unidad_principal": "SOBRE PLASTICO",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "BOLSA"
  },
  {
    "codigo": "INSU22",
    "nombre": "BOLSA DE ORINA NIPRO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "41018AZC01",
    "cantidad_principal": 0,
    "unidad_principal": "SOBRE PLASTICO",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "BOLSA"
  },
  {
    "codigo": "INSU20",
    "nombre": "BOLSA DE ORINA NORMAL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "240805",
    "cantidad_principal": 0,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "BOLSA"
  },
  {
    "codigo": "INSU20",
    "nombre": "BOLSA DE ORINA NORMAL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "240805",
    "cantidad_principal": 14,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 14,
    "unidad_secundaria": "BOLSA"
  },
  {
    "codigo": "MEDI3",
    "nombre": "ENOXPRIM 20",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "A0490021",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "JERINGA PRERELLENADA"
  },
  {
    "codigo": "INSU6",
    "nombre": "AGUJA HIPODERMICA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20250228",
    "cantidad_principal": 2.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 273,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDI8",
    "nombre": "AMITRIPTILINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AH1C401",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 100,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "INSU54",
    "nombre": "JERINGA 3ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "221105",
    "cantidad_principal": 6.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 667,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU111",
    "nombre": "VENDA COBAN 3\"",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23755133",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "FORFIG 300",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "939875",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDINT26",
    "nombre": "SIDERAL FOLIC",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "3034",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MED",
    "nombre": "FORACORT",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "501",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 18,
    "unidad_secundaria": "TABLETA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CEFIXIMA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "62503",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MED",
    "nombre": "ALGODON 400 GR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4000225",
    "cantidad_principal": 0.8,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "ROLLOS"
  },
  {
    "codigo": "MED",
    "nombre": "ALGODON 400 GR",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AP4000325",
    "cantidad_principal": 0.7,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "ROLLOS"
  },
  {
    "codigo": "MEDIF",
    "nombre": "COTRIMOXAZOL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "102426",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 64,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "INSU102",
    "nombre": "VENDA DE GASA 5 CM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2020304",
    "cantidad_principal": 0.8,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU102",
    "nombre": "VENDA DE GASA 5 CM",
    "responsable": "NAIKA ANILA MALDONADO PALENKE",
    "lote": "20230204",
    "cantidad_principal": 0,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU102",
    "nombre": "VENDA DE GASA 5 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20230902",
    "cantidad_principal": 0,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU107",
    "nombre": "VENDA ELASTICA 5 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20240910",
    "cantidad_principal": 2.6,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 31,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU107",
    "nombre": "VENDA ELASTICA 5 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "51246",
    "cantidad_principal": 0.1,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "FILINAR G",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "117557",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MED",
    "nombre": "FILINAR G",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "998174",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "INSU55",
    "nombre": "JERINGA 5 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20231030",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU55",
    "nombre": "JERINGA 5 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20240121",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 200,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU58",
    "nombre": "JERINGA 50 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "240805",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 44,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CIPROFLOXACINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "225123",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 86,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CEFALEXINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "112424",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 40,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MED",
    "nombre": "COLINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "120",
    "cantidad_principal": 0,
    "unidad_principal": "VIAL",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI7774",
    "nombre": "IMIPENEM CILASTATINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "250718",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI7774",
    "nombre": "IMIPENEM CILASTATINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "250718",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI7774",
    "nombre": "IMIPENEM CILASTATINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "250718",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI7774",
    "nombre": "IMIPENEM CILASTATINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "26214",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI7774",
    "nombre": "IMIPENEM CILASTATINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "A26214",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DICLOXACILINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "42505",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 64,
    "unidad_secundaria": "CAPSULAS"
  },
  {
    "codigo": "MEDIF",
    "nombre": "NISTATINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "52558",
    "cantidad_principal": 4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "NISTATINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "72413",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "HEPARINA SODICA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "250403",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDLF5",
    "nombre": "TIAMIGAL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20T24001",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDH",
    "nombre": "IVERMECTINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "85129",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDISLCH6",
    "nombre": "CARVEDILOL 6.25",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "E0624 23385",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDH",
    "nombre": "CEFACRIS 500",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "9418",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "INSU103",
    "nombre": "VENDA DE GASA 7,5 CM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "0000",
    "cantidad_principal": 1.5,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 18,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU103",
    "nombre": "VENDA DE GASA 7,5 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20220504",
    "cantidad_principal": 0,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU173",
    "nombre": "STYPCEL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "0501241219",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "INSU173",
    "nombre": "STYPCEL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "524240514",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDINT15",
    "nombre": "MUXATIL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "26638",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIC7",
    "nombre": "ACETILCISTEINA 200",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "003056",
    "cantidad_principal": 1.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 14,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MUXATIL AMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "34601",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MUXATIL AMP",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "35651",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MUXATIL AMP",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "35651",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MUXATIL AMP",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "35651",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MUXATIL AMP",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "35652",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV1",
    "nombre": "FLUICETIL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "U200322402",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7773",
    "nombre": "ACETILCISTEINA 300MG/3ML AMPOLLA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "CN007027",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "FLUIDIMED PRO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6971",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ACICLOVIR 400",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "724112",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "VIRUSAN 500",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "36645",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ASA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "33207",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 61,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIN6",
    "nombre": "BILISAN 100",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "26441",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 116,
    "unidad_secundaria": "TABLETAS"
  },
  {
    "codigo": "MEDIV",
    "nombre": "CARDIO VIMIN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "36118",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 17,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDM",
    "nombre": "RIXAM 1000",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "M10925",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDF",
    "nombre": "XAMIC",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6333",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDF",
    "nombre": "PAUSE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ELF 8AU5003",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDF",
    "nombre": "PAUSE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ELF8AU5003",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDF",
    "nombre": "PAUSE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ELF8AU5003",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDF",
    "nombre": "PAUSE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ELF8AU5003",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDF",
    "nombre": "PAUSE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ELF8AU5003",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "NEOTREX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "5340",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7801",
    "nombre": "TRANEST ACIDO TRANEXAMICO 500MG/5ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "1111250",
    "cantidad_principal": 6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7801",
    "nombre": "TRANEST ACIDO TRANEXAMICO 500MG/5ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "11250",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7771",
    "nombre": "ADECUAN AMPOLLA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "1210724",
    "cantidad_principal": 0,
    "unidad_principal": "AMPOLLA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7771",
    "nombre": "ADECUAN AMPOLLA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "540725",
    "cantidad_principal": 0,
    "unidad_principal": "AMPOLLA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7771",
    "nombre": "ADECUAN AMPOLLA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "540725",
    "cantidad_principal": 10,
    "unidad_principal": "AMPOLLA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ADRENALINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "33959",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "INSU115",
    "nombre": "AEROCAMARA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "22N0474",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "AYV",
    "nombre": "AGUA PARA INYECCION",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "35323",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 23,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "INSU3",
    "nombre": "AGUJA HIPODERMICA Nº 18G X1 1/2\"",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "250415",
    "cantidad_principal": 3.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 368,
    "unidad_secundaria": "SACHET + AGUJA"
  },
  {
    "codigo": "INSB5",
    "nombre": "PERICAN 18GX1/4",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23H13G8F01",
    "cantidad_principal": 3,
    "unidad_principal": "ESTUCHE",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "AGUJA"
  },
  {
    "codigo": "MED",
    "nombre": "PENCAN Nº 27",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24G01H8B03",
    "cantidad_principal": 10,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "INSU144",
    "nombre": "HILO NYLON 6/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20282555",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU144",
    "nombre": "HILO NYLON 6/0",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "IA 247131",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU156",
    "nombre": "HILO CAT GUT CROMADO 4/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20122773",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 36,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU157",
    "nombre": "HILO CAT GUT CROMADO 5/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "21120342",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 36,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU164",
    "nombre": "HILO SEDA 5/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20301582",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU163",
    "nombre": "HILO SEDA 4/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20702001",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 28,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU143",
    "nombre": "HILO NYLON Nº 5/0",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "21255745",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 23,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU143",
    "nombre": "HILO NYLON Nº 5/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "247130",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU141",
    "nombre": "HILO NYLON 3/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "21151775",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU141",
    "nombre": "HILO NYLON 3/0",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "21255925",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU141",
    "nombre": "HILO NYLON 3/0",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "247126",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU141",
    "nombre": "HILO NYLON 3/0",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "247127",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 22,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU142",
    "nombre": "HILO NYLON 4/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "IA247129",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 27,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU149",
    "nombre": "HILO CAT GUT SIMPLE 4/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20605973",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU161",
    "nombre": "HILO SEDA 3/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "202413608",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU161",
    "nombre": "HILO SEDA 3/0",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "247707",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU",
    "nombre": "HILO POLIPROPILENO 4",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20605733",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU169",
    "nombre": "HILO POLIPROPILENO 5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20902611",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 35,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU140",
    "nombre": "HILO NYLON Nº 2/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20516324",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 13,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU147",
    "nombre": "HILO CAT GUT SIMPLE 2/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20301392",
    "cantidad_principal": 1.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 42,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU148",
    "nombre": "HILO CAT GUT SIMPLE 3/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20806781",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU155",
    "nombre": "HILO CAT GUT CROMADO 3/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20622054",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 34,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU162",
    "nombre": "HILO SEDA 3/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20517664",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU154",
    "nombre": "HILO CAT GUT CROMADO 2",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20108944",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 27,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU138",
    "nombre": "HILO NYLON Nº 1",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20806801",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 107,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU150",
    "nombre": "HILO CAT GUT CROMADO 0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20581741",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 24,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU145",
    "nombre": "HILO CAT GUT SIMPLE 0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20301382",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 23,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU146",
    "nombre": "HILO CAT GUT SIMPLE 1",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20622934",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU151",
    "nombre": "HILO CAT GUT CROMADO 1",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20300754",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 29,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU",
    "nombre": "HILO CAT GUT CROMADO 2/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "21121032",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "EQUI6770",
    "nombre": "AGUJA HIPODERMICA 22G 1/2",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "221105",
    "cantidad_principal": 87,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 87,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDI7804",
    "nombre": "AGUJA HIPODERMICA 23G OPTIMED",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "240805",
    "cantidad_principal": 4.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 444,
    "unidad_secundaria": "AGUJA"
  },
  {
    "codigo": "INSU159",
    "nombre": "HILO SEDA 1",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "21043655",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ALBENDAZOL 200",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "42521",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "COMPRIMIDO MASTICABLE"
  },
  {
    "codigo": "MEDM",
    "nombre": "ALBURX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "P100791389",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI7799",
    "nombre": "ALBUMINA HUMANA 20%",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AD20F25055",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "INSU11",
    "nombre": "ALGODON 100 GR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "1000325",
    "cantidad_principal": 12,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "ROLLO"
  },
  {
    "codigo": "INSU11",
    "nombre": "ALGODON 100 GR",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "124",
    "cantidad_principal": 0,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "ROLLO"
  },
  {
    "codigo": "MEDF",
    "nombre": "ALIVIOL PLUS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "PS4031A",
    "cantidad_principal": 4.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 410,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDFS4",
    "nombre": "MUXOL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "035853",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI",
    "nombre": "INTIBROXOL INFANTIL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "35940",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "BRONCOFLU",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "62529",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDS7",
    "nombre": "BROXMOL MR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "BAL2404",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "INTIBROXOL AMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "34008",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI",
    "nombre": "INTIBROXOL ADULTO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24252",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI7824",
    "nombre": "UROGRAFINA 76%",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "MA04KLH",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDAYV4",
    "nombre": "GLUMIKIN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "1A24024",
    "cantidad_principal": 2.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 71,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDLQ1",
    "nombre": "GALAMINOFIL 250",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "TP2290124",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIIN-B24",
    "nombre": "AMINOPLASMAL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "252678061",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIIN-B24",
    "nombre": "AMINOPLASMAL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "252678061",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIIN-B24",
    "nombre": "AMINOPLASMAL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "252678061",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIIN-B24",
    "nombre": "AMINOPLASMAL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "252678061",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIT2",
    "nombre": "MOXILIN 1G",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2404313",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 80,
    "unidad_secundaria": "COMPRIMIDO RECUBIERTO"
  },
  {
    "codigo": "MEDC",
    "nombre": "AMOXICILINA 1",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "3238",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 51,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDB",
    "nombre": "IBL 1500",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4Q1",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDF",
    "nombre": "AMOXIDIN 250",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2701223",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDF",
    "nombre": "AMOXIDIN 500",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2761223",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDC",
    "nombre": "AMOXICILINA 500 MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6950",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIT6",
    "nombre": "MOXILIN 500 MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AMX5S042305",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDFS1",
    "nombre": "AMOVAL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "047193",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDF",
    "nombre": "AMOXIDIN PLUS FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2501002",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDF",
    "nombre": "AMOXIDIN PLUS FORTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2503006",
    "cantidad_principal": 1.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 22,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIBPH7",
    "nombre": "BIOTIX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7107",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "COMPRIMIDO RECUBIERTO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "AMPICILINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "25750",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 28,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MED",
    "nombre": "MORFINA 10 MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "25095",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 17,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7780",
    "nombre": "RIXAM 500",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "M11073",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7816",
    "nombre": "NEOSTIGMINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "36378",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7816",
    "nombre": "NEOSTIGMINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "36378",
    "cantidad_principal": 2.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 50,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI6691",
    "nombre": "LANZOPRAL AMPOLLA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "899",
    "cantidad_principal": 5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI6689",
    "nombre": "FLOGIATRIN AMPOLLA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2411964001",
    "cantidad_principal": 4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI6761",
    "nombre": "METOCLOPRAMIDA 10MG AMPOLLA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "0000034384",
    "cantidad_principal": 0,
    "unidad_principal": "AMPOLLA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI6761",
    "nombre": "METOCLOPRAMIDA 10MG AMPOLLA",
    "responsable": "NAIKA ANILA MALDONADO PALENKE",
    "lote": "240584",
    "cantidad_principal": 26,
    "unidad_principal": "AMPOLLA",
    "cantidad_secundaria": 26,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI6761",
    "nombre": "METOCLOPRAMIDA 10MG AMPOLLA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2405884",
    "cantidad_principal": 100,
    "unidad_principal": "AMPOLLA",
    "cantidad_secundaria": 100,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI6761",
    "nombre": "METOCLOPRAMIDA 10MG AMPOLLA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "34384",
    "cantidad_principal": 0,
    "unidad_principal": "AMPOLLA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI6763",
    "nombre": "METOCLOPRAMIDA 10MG MUNDO PHARMA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "250506",
    "cantidad_principal": 8,
    "unidad_principal": "AMPOLLA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI6762",
    "nombre": "GENTAMICINA 80ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "123456",
    "cantidad_principal": 80,
    "unidad_principal": "AMPOLLA",
    "cantidad_secundaria": 80,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI6762",
    "nombre": "GENTAMICINA 80ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "240817",
    "cantidad_principal": 4,
    "unidad_principal": "AMPOLLA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDF",
    "nombre": "TAMBOL FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "T02W02",
    "cantidad_principal": 1.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 25,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDI7817",
    "nombre": "QUETOROL TRAM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "38351",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX B1B6B12 FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AA8A",
    "cantidad_principal": 18,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 18,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI7811",
    "nombre": "GOLPEX SPRAY",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "GS20403",
    "cantidad_principal": 5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX B1B6B12 AMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AC9Z",
    "cantidad_principal": 10,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX B1B6B12 AMP",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ADPF",
    "cantidad_principal": 14,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 14,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI11",
    "nombre": "GABENOL 1",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "242915",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ENCIFER",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ELF8AM5011",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDM9",
    "nombre": "AERONID 50",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4183",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AEROSOL"
  },
  {
    "codigo": "INSU41",
    "nombre": "FILTRO ANTIBACTERIANO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20230815",
    "cantidad_principal": 0.3,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDN2",
    "nombre": "NOVO PENCIL 12.6.6",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "BI272",
    "cantidad_principal": 5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "VIAL+AMPOLLA+JERINGA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "PENTRAX AC",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "1219",
    "cantidad_principal": 1.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 16,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDA",
    "nombre": "MEROPENEM 1 GR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "200428",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDA",
    "nombre": "MEROPENEM 1 GR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "200428",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDA",
    "nombre": "VANCOMICINA 1GR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "290184",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDA",
    "nombre": "VANCOMICINA 1GR",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "94437",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDA",
    "nombre": "VANCOMICINA 1GR",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "94437",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 21,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDB",
    "nombre": "TRIAPEN FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ADJD",
    "cantidad_principal": 18,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 18,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIFA",
    "nombre": "BACITRACINA NEOMICINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "72411",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MEDI7809",
    "nombre": "CEFTRIAXON 1GR",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2403304",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 14,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI7809",
    "nombre": "CEFTRIAXON 1GR",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2403304",
    "cantidad_principal": 1.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 48,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DICLOXACILINA 250 MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "12559",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI7788",
    "nombre": "BACTICEL FORTE SUSPENSION",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AA3L",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI7789",
    "nombre": "BACTICEL SUSPENSION",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ACWR",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "POM24",
    "nombre": "RIFAMICINA 10MG/ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "243401",
    "cantidad_principal": 9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDB",
    "nombre": "ESPASMO-DIOXADOL PLUS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AB81",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDB",
    "nombre": "ESPASMO-DIOXADOL PLUS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AD2",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDB",
    "nombre": "ESPASMO-DIOXADOL PLUS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AD2I",
    "cantidad_principal": 12,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDB",
    "nombre": "ESPASMO-DIOXADOL PLUS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ADRN",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDB",
    "nombre": "ESPASMO-DIOXADOL PLUS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "PK009",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDBP",
    "nombre": "ESPASMOLOXADIM FORTE COMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6272",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 43,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIM6",
    "nombre": "VIADIL COMPUESTO NF",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "M09691",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDM7",
    "nombre": "VIADIL COMPUESTO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "M09627",
    "cantidad_principal": 1.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "PAR DE AMPOLLAS"
  },
  {
    "codigo": "MEDI7784",
    "nombre": "ESPASMO DIOXADOL GOTAS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AB9B",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDM",
    "nombre": "RIXAM 250",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "M09465",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDM13",
    "nombre": "RIXAM 250",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "M08718",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "ANTIGRIPAL COMPUESTO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "31502",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 201,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDB",
    "nombre": "ESPASMODIOXADOL PLUS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4PD",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 197,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDB",
    "nombre": "OXAR D",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6569",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 26,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDC",
    "nombre": "POTASIO CL 1.3 MEQ",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6578",
    "cantidad_principal": 1,
    "unidad_principal": "FRASCO",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDBP",
    "nombre": "CRONOBECOR",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "007888",
    "cantidad_principal": 3,
    "unidad_principal": "ESTUCHE",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDBP",
    "nombre": "CRONOBECOR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7878",
    "cantidad_principal": 0,
    "unidad_principal": "ESTUCHE",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI7812",
    "nombre": "FLOGOCOX 90",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ABRS",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 27,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDI7793",
    "nombre": "QUETIAPINA 100MG",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20551",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 19,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NOVONOX 40",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AB13931B",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "JERINGA PRELLENADA"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NOVONOX 40",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AB13931B",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "JERINGA PRELLENADA"
  },
  {
    "codigo": "MEDI7791",
    "nombre": "TUSABRON",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ABSN",
    "cantidad_principal": 5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DEXTROMETORFAN O 10 MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "62530",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDM",
    "nombre": "TUSILEXIL D",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23292",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "INSU38",
    "nombre": "EQUIPO ARCOMED AG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23PH171",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIN",
    "nombre": "KIN GINGIVAL COMPLEX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24P32",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "CAJA"
  },
  {
    "codigo": "MEDF",
    "nombre": "ALIVIOL ANTIGRIPAL CAP COLORES",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AN5002",
    "cantidad_principal": 1.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 130,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "TORNIX 20",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "33066",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 13,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "AYV5",
    "nombre": "ATROPINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "1771",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 24,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV5",
    "nombre": "ATROPINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "XD146",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "INSU13",
    "nombre": "BAJALENGUA ADULTO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "0001",
    "cantidad_principal": 5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 500,
    "unidad_secundaria": "PALETAS"
  },
  {
    "codigo": "INSU13",
    "nombre": "BAJALENGUA ADULTO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "202404",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 200,
    "unidad_secundaria": "PALETAS"
  },
  {
    "codigo": "MEDF",
    "nombre": "LABEBLOCK",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "E23010",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIB3",
    "nombre": "CRONOCORTEROID",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AC6Z",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL + JERINGA"
  },
  {
    "codigo": "MEDIB3",
    "nombre": "CRONOCORTEROID",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "PK008-0",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL + JERINGA"
  },
  {
    "codigo": "MEDIB3",
    "nombre": "CRONOCORTEROID",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "PK010-0",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL + JERINGA"
  },
  {
    "codigo": "MEDIB3",
    "nombre": "CRONOCORTEROID",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "PK011-0",
    "cantidad_principal": 4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "VIAL + JERINGA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BECOR RAPILENTO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6870",
    "cantidad_principal": 3,
    "unidad_principal": "ESTUCHE",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTYPIREN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "3HP",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTYPIREN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "42H",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTYPIREN",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "4V3",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 18,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7776",
    "nombre": "BETISTIN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "957474",
    "cantidad_principal": 1.9,
    "unidad_principal": "BLISTER",
    "cantidad_secundaria": 19,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "AYV5",
    "nombre": "BICARBONATO DE SODIO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "32436",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV5",
    "nombre": "BICARBONATO DE SODIO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "34677",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 50,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "BIO ELECTRO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2034335",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 88,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDS6",
    "nombre": "BIOCICATRIZANTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "BC4V404",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MEDS6",
    "nombre": "BIOCICATRIZANTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "BC4V505",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MEDM",
    "nombre": "CORENTEL 5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "M08949",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDB22",
    "nombre": "BOLSA DE COLOSTOMIA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "45852",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU21",
    "nombre": "BOLSA DE ORINA PEDIATRICA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20240805",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 44,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "AYV8",
    "nombre": "BUPIVACAINA 0,5% 10 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "35644",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 21,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV8",
    "nombre": "BUPIVACAINA 0,5% 10 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "35644",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI12",
    "nombre": "GABENOL 2",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "242628",
    "cantidad_principal": 1.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "INSU83",
    "nombre": "TESTIGO HUMEDO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "1327",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU84",
    "nombre": "TESTIGO SECO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2283",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INS29-1",
    "nombre": "CANULA DE ASPIRACION Nº 6",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23755133",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDINT28",
    "nombre": "SIDEAL FORTE INT",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "M40367",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "INSU170",
    "nombre": "HILO SEDA NEGRA 1",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20282595",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "CARRETE"
  },
  {
    "codigo": "INSU171",
    "nombre": "HILO SEDA NEGRA 2",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20806993",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "CARRETA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "TROXOLINA 500",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "00998",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFAZOHAN",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "08525",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 50,
    "unidad_secundaria": "VIALES"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFAZOHAN",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "11423",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 16,
    "unidad_secundaria": "VIALES"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFAZOHAN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "V11423",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "VIALES"
  },
  {
    "codigo": "MEDB",
    "nombre": "MAXIBIOTIC 1000",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4AW",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDB",
    "nombre": "MAXIBIOTIC 1000",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AFPA",
    "cantidad_principal": 9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDB",
    "nombre": "FIXIM 400",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "007499",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "FIXIM 100",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6653",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDF",
    "nombre": "SITEX 100",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AP5001",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDBP",
    "nombre": "FIXIM FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7753",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDF",
    "nombre": "SITEX FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "BV5001",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFOTAXIMA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "10535",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFOTAXIMA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "11539",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFOTAXIMA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "V10535",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 50,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFOTAXIMA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "V10535",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFOTAXIMA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "V10535",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFOTAXIMA",
    "responsable": "JUDITH G. PANIAGUA CRUZ",
    "lote": "V10535(PAOLA FRANCHESKA FLORES CRUZ)",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDT",
    "nombre": "CEFOTAXIM 1",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2504302",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI14",
    "nombre": "IFOTAXIMA 1000",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "25751",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 25,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "AYV13",
    "nombre": "CEFTADIZIMA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "230706",
    "cantidad_principal": 2.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 29,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "AYV13",
    "nombre": "CEFTADIZIMA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "V123112",
    "cantidad_principal": 2.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 25,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "C TRIX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "V09531",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "C TRIX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "V09531",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIB1A",
    "nombre": "TIAXAL I M",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AA2A/4V2",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "VIAL+SOLVENTE +JERINGA"
  },
  {
    "codigo": "MEDI15",
    "nombre": "CEFTRIAX 1000",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "A26109",
    "cantidad_principal": 1.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 48,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIB6",
    "nombre": "TIAXAL IV",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4J1-3WU",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA + SOLVENTE"
  },
  {
    "codigo": "MEDI",
    "nombre": "CEPILLO CITOLOGICO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20240426",
    "cantidad_principal": 0.8,
    "unidad_principal": "SOBRES",
    "cantidad_secundaria": 40,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU172",
    "nombre": "CERA DE HUESO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20626204",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDIIN-B45",
    "nombre": "CERTOFIX DUO PAED S 413",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "25B01A8551",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "MEDIIN-B45",
    "nombre": "CERTOFIX DUO PAED S 413",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "SL231535",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "INSU7818",
    "nombre": "CERTOFIX DUO S720",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25A16A8551",
    "cantidad_principal": 1,
    "unidad_principal": "PACK",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PACK"
  },
  {
    "codigo": "INSU7818",
    "nombre": "CERTOFIX TRIO S720",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25F19A8581",
    "cantidad_principal": 1,
    "unidad_principal": "PACK",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PACK"
  },
  {
    "codigo": "MEDB",
    "nombre": "REMITEX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ABCW",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "INSU35",
    "nombre": "CONECTOR CONTRASTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "090111147501",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "INSU35",
    "nombre": "CONECTOR CONTRASTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "090111147501",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MEDBP",
    "nombre": "PROCIN DIGEST",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7896",
    "cantidad_principal": 1.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 88,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "AYV15",
    "nombre": "CIPROFLOXACINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "160-50X13",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "AYV15",
    "nombre": "CIPROFLOXACINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "36825",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "AYV15",
    "nombre": "CIPROFLOXACINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "36825",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "AYV15",
    "nombre": "CIPROFLOXACINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "50X13",
    "cantidad_principal": 2.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 79,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDB",
    "nombre": "SEPTICIDE 500",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AB8S",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDB",
    "nombre": "SEPTICIDE 500",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ADVU",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDQ4",
    "nombre": "FORTINIL 1000",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "250977",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDQ4",
    "nombre": "FORTINIL 1000",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "250977",
    "cantidad_principal": 12,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "INSU123",
    "nombre": "CLAMP UMBILICAL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20530401",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU123",
    "nombre": "CLAMP UMBILICAL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25449",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "AYV14",
    "nombre": "CLINDALCOS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "278-5SX03",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 38,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU124",
    "nombre": "CLIP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "708D58",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "MEDC",
    "nombre": "CLOPIDOGREL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6036",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIB34",
    "nombre": "TUSIGEN INFANTIL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "4AR",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIB35",
    "nombre": "TUSIGEN",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AAND",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIS1",
    "nombre": "SINALERG 4",
    "responsable": "JORGE MIGUEL VILLCA LOPEZ",
    "lote": "1260924",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 14,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "AYV17",
    "nombre": "CLORFERINAMINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "231262",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV17",
    "nombre": "CLORFERINAMINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "66943",
    "cantidad_principal": 9.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 95,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIS6",
    "nombre": "CLOREX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "300425",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "BUPIGOBBI 0.5% HIPERBARICA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "BEH105",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDICR3",
    "nombre": "KETAMIN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "500298",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDPR9",
    "nombre": "ANESTEARS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "211094",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "GOTAS"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CLORURO DE POTASIO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "0000034832",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CLORURO DE POTASIO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "33848",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CLORURO DE POTASIO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "35885",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 48,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDM",
    "nombre": "NASOXY SPRAY",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "085355",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "SPRAY"
  },
  {
    "codigo": "MEDI",
    "nombre": "CLORURO DE SODIO 10",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "33231",
    "cantidad_principal": 2.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 135,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV19",
    "nombre": "CLORURO DE SODIO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "33231",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 94,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV19",
    "nombre": "CLORURO DE SODIO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "33231",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV19",
    "nombre": "CLORURO DE SODIO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "3553",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDFS9",
    "nombre": "NEUROVAL CD 10 MG",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "S034744",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDFS9",
    "nombre": "NEUROVAL CD 10 MG",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "S103073",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 15,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CLOTRIM 1%",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "102524",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "BETACLOX 1 GR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2403305",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 15,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIV",
    "nombre": "COBA VIMIN 25000",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24907",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "DEXAMINO FUERTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "5392",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "MEDI7823",
    "nombre": "DEXAMINO FUERTE AMPOLLA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "007772",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "INSU42",
    "nombre": "FRASCO DE ORINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "041825",
    "cantidad_principal": 0.3,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 14,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU42",
    "nombre": "FRASCO DE ORINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20240420",
    "cantidad_principal": 0,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDI7830",
    "nombre": "CIPRODEX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "X120735",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MED",
    "nombre": "COLLARIN S M L",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "54321",
    "cantidad_principal": 5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "COLLAR"
  },
  {
    "codigo": "MEDSF",
    "nombre": "IBUFLAMAR P",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23070419",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIV",
    "nombre": "COMPLEJO B VIMIN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "32973",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIV",
    "nombre": "COMPLEJO B VIMIN COMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "31497",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 16,
    "unidad_secundaria": "TABLETA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "COMPLEJO B AMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "34379",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "COMPLEJO B AMP",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "34379",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MED",
    "nombre": "PACO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "917901",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDH",
    "nombre": "IBUMIGRAM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "5581",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "IBUPROFENO 600 COMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "524124",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 33,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDI6692",
    "nombre": "MEGATADINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "M12442",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "INSU61",
    "nombre": "LLAVE DE 3 VIAS CON ALARGADOR 50 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "722406",
    "cantidad_principal": 0.1,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDI7785",
    "nombre": "CORTYPIREN GOTAS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ACZY",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "GOTERO"
  },
  {
    "codigo": "POM4",
    "nombre": "SUPRACORTIN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "31477",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM4",
    "nombre": "SUPRACORTIN",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "36121",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM4",
    "nombre": "SUPRACORTIN",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "36121",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM22",
    "nombre": "TOPICREM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2110225",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "EQUI7781",
    "nombre": "CUBRE CALZADOS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "0000",
    "cantidad_principal": 2,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 50,
    "unidad_secundaria": "PAR"
  },
  {
    "codigo": "INSU36",
    "nombre": "CUBREZAPATOS QX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "BIO037",
    "cantidad_principal": 2,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 100,
    "unidad_secundaria": "PARES"
  },
  {
    "codigo": "MEDIM2",
    "nombre": "DAPAGLICIN 10",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "N10105",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "COMPRIMIDO RECUBIERTO"
  },
  {
    "codigo": "MEDF",
    "nombre": "DAPAMET 10/1000",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "EMV241191A",
    "cantidad_principal": 1.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 35,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDI",
    "nombre": "DEMOTIL AG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "33180",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "GOTERO"
  },
  {
    "codigo": "MEDI",
    "nombre": "DEMOTIL GOTAS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "29305",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "GOTERO"
  },
  {
    "codigo": "MEDI6760",
    "nombre": "DEXAMETASONA OFTALMICA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "240902",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "CAJA"
  },
  {
    "codigo": "AYV23",
    "nombre": "DEXACOFAZONA 4",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7011",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV23",
    "nombre": "DEXACOFAZONA 4",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7013",
    "cantidad_principal": 1.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 124,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTISTAMIN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4T8",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 16,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTISTAMIN FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ACRP",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTISTAMIN FORTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ACRP",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTISTAMIN FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ADKJ",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTISTAMIN FORTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AFTV",
    "cantidad_principal": 1.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 34,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTISTAMIN FORTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AFTX",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DEXACOFAZONA 8",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "007217",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DEXACOFAZONA 8",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "12345",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DEXACOFAZONA 8",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7036",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DEXACOFAZONA 8",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "CN007644",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 90,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIBPH25",
    "nombre": "CORTIMED 8",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7212",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 17,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "KETOFLEX DUO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7474",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DEXALIVIUM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "0000030013",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DEXALIVIUM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "30013",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DEXALIVIUM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "30013",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DEXALIVIUM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "37583",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DEXALIVIUM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "37583",
    "cantidad_principal": 1.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 15,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIPM5",
    "nombre": "TAZAROL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "EP928",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIPM5",
    "nombre": "TAZAROL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "EV940",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDCR",
    "nombre": "COMPAZ",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "50020031",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDH",
    "nombre": "DICLOFENACO 50",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "3473",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 307,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDI7",
    "nombre": "FLAMADIN PLUS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "112562",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "CAPSULA BLANDA"
  },
  {
    "codigo": "MEDI7",
    "nombre": "FLAMADIN PLUS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "122551",
    "cantidad_principal": 19,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 190,
    "unidad_secundaria": "CAPSULA BLANDA"
  },
  {
    "codigo": "MEDIBPH14",
    "nombre": "NOVADOL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "5828",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA DISPENSADORA",
    "cantidad_secundaria": 58,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDTC3",
    "nombre": "LERTUS GEL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "72352",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MEDFS11",
    "nombre": "NERBEDOL B 12 10000",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "244",
    "cantidad_principal": 1.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DICLOFENACO 75",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "006745",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DICLOFENACO 75",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "03241",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DICLOFENACO 75",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "7103241",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 18,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CLOFEXAN RELAX FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4K4",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDIBPH20",
    "nombre": "DOLOCOFAMIN 25.000 B12 FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6211",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "ESTUCHE UNIDOSIS"
  },
  {
    "codigo": "MEDIBPH20",
    "nombre": "DOLOCOFAMIN 25.000 B12 FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6750",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "ESTUCHE UNIDOSIS"
  },
  {
    "codigo": "MEDIBPH13",
    "nombre": "NOVADOL 75",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "008040",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 54,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDIBPH13",
    "nombre": "NOVADOL 75",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7715",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDIBPH12",
    "nombre": "NOVADOL FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "006875",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "COMPRIMIDO RECUBIERTO"
  },
  {
    "codigo": "MEDIBPH12",
    "nombre": "NOVADOL FORTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "008295",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "COMPRIMIDO RECUBIERTO"
  },
  {
    "codigo": "MEDIBPH12",
    "nombre": "NOVADOL FORTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "CN007987",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "COMPRIMIDO RECUBIERTO"
  },
  {
    "codigo": "MEDI4",
    "nombre": "FLAMADIN B12",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "25342",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI4",
    "nombre": "FLAMADIN B12",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "B26210",
    "cantidad_principal": 1.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI5",
    "nombre": "FLAMADIN B12 FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2410161",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI5",
    "nombre": "FLAMADIN B12 FORTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "B2511150",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI5",
    "nombre": "FLAMADIN B12 FORTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "B2511150",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7792",
    "nombre": "CLOFEXAN FORTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AA4D",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DICLOFENACO 1%",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "92570",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MEDB",
    "nombre": "CLOFEXAN50",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AB2Z",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 55,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIB20",
    "nombre": "CLOFENAC 75",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2B0",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 62,
    "unidad_secundaria": "COMPRIMIDO RECUBIERTO"
  },
  {
    "codigo": "MEDIB9",
    "nombre": "CLOFENAC 75",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AB0V",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIB8",
    "nombre": "CLOFENAC RELAX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "33621",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIB8",
    "nombre": "CLOFENAC RELAX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AAQH",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIB8",
    "nombre": "CLOFENAC RELAX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AD6M",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIB8",
    "nombre": "CLOFENAC RELAX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AD6M",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "LIDRAMINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "25306",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDSF",
    "nombre": "GRAVOL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "GRL009EE",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 13,
    "unidad_secundaria": "TABLETA"
  },
  {
    "codigo": "MEDINT3",
    "nombre": "DIPOSAN 100",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20348",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 60,
    "unidad_secundaria": "TABLETA MASTTICABLE"
  },
  {
    "codigo": "MEDIBPH18",
    "nombre": "ESPASMOLOXADIM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6397",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "CAJA X 2 AMP"
  },
  {
    "codigo": "MEDIBPH16",
    "nombre": "ESPASMOLOXADIM FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6214",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "CAJA X 2 AMP"
  },
  {
    "codigo": "0",
    "nombre": "DIOXADOL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ABLS",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIB11",
    "nombre": "DIOXADOL FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AB9T",
    "cantidad_principal": 1.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 23,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIB11",
    "nombre": "DIOXADOL FORTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AB9U",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 40,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIB10",
    "nombre": "DIOXADOL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AB8Z",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIB10",
    "nombre": "DIOXADOL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AC0U",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIB10",
    "nombre": "DIOXADOL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AC0U",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIB10",
    "nombre": "DIOXADOL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AF6M",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 40,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7807",
    "nombre": "DIPROFEN 400MG CAPSULAS BLANDAS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "92524",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDI7808",
    "nombre": "DIPROFEN 600MG CAPSULAS BLANDAS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "152543",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDI4",
    "nombre": "DIURENYL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "PN15VK85",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MED",
    "nombre": "DOMPER DIGEST",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "75286",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDITC1",
    "nombre": "DOMPER",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "73200",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 28,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "INSU116",
    "nombre": "LAPIZ DE ELECTROBISTURI",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "100301209",
    "cantidad_principal": 5,
    "unidad_principal": "ESTUCHE",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "INSU116",
    "nombre": "LAPIZ DE ELECTROBISTURI",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "202504161101",
    "cantidad_principal": 10,
    "unidad_principal": "ESTUCHE",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "MEDI6766",
    "nombre": "ELECTRODO ADULTO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "251122 0289",
    "cantidad_principal": 172,
    "unidad_principal": "PIEZAS",
    "cantidad_secundaria": 172,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU126",
    "nombre": "ELECTRODOS PEDIATRICO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "D453-0",
    "cantidad_principal": 1.3,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 64,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDFS3",
    "nombre": "KEVAL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "049234",
    "cantidad_principal": 1.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDF",
    "nombre": "EMPAGLYP 25",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AP250028",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 26,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "INSU40",
    "nombre": "ESPATULA CERVICAL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "12345",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 66,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NOVONOX 60",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "11111",
    "cantidad_principal": 2.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "JERINGA PRELLENADA"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NOVONOX 60",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AC12431D",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "JERINGA PRELLENADA"
  },
  {
    "codigo": "MEDB21",
    "nombre": "INTRAFIX PRIMELINE AIR FS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2304149",
    "cantidad_principal": 0.4,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDLQ7",
    "nombre": "ERGO 0.2",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "T36713",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 61,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDLQ6",
    "nombre": "ERGO INYECTABLE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "DLP5008",
    "cantidad_principal": 1.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 17,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MED",
    "nombre": "ESPARADRAPO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "04535160",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MED",
    "nombre": "ESPARADRAPO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "11111",
    "cantidad_principal": 1.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 22,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MED",
    "nombre": "ESPECULO DESECHABLE S M L",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20231228",
    "cantidad_principal": 12,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "ESPECULO"
  },
  {
    "codigo": "MEDH",
    "nombre": "UROHAN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "83156",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "TABLETA"
  },
  {
    "codigo": "MEDH",
    "nombre": "UROHAN",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "C083156",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 40,
    "unidad_secundaria": "TABLETA"
  },
  {
    "codigo": "MEDLQ14",
    "nombre": "VARDACTONE 25",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "T24418",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDM",
    "nombre": "DEOFLORA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "H325001",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "AMPOLLA ORAL"
  },
  {
    "codigo": "INSU98",
    "nombre": "INTUBATING STYLET",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2211022333",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDBP",
    "nombre": "PLATELET",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "3576",
    "cantidad_principal": 1.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI6771",
    "nombre": "ETILEFRINA CLORHIDRATO 10MG",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "30106",
    "cantidad_principal": 30,
    "unidad_principal": "AMPOLLA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIIN-B32",
    "nombre": "EXADROP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2523001",
    "cantidad_principal": 0.5,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU43",
    "nombre": "FRASCO DE HECES",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "74987",
    "cantidad_principal": 1.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 62,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDI7790",
    "nombre": "TOCEX JARABE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "4UZ",
    "cantidad_principal": 4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDB15",
    "nombre": "EXTENSOFIX 120CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2314319",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDLQ3",
    "nombre": "FENITOGAL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "125051",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "0",
    "nombre": "FENITOINA 100",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "E0524",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MED",
    "nombre": "FENTANILO 0.5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "5010061",
    "cantidad_principal": 17.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 89,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MED",
    "nombre": "FLAVO CKR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "416528",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 15,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "FLORESTOR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "8652",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDIN6",
    "nombre": "MYCOTIX 200",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "19841",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "FLUOXOL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "182-5OX01",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 32,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "FLUOXOL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "5JUW04",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 40,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDI7796",
    "nombre": "CELEPID 20%",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2251256",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI7796",
    "nombre": "CELEPID 20%",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2251256",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CODEINA 10 MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "72501",
    "cantidad_principal": 4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI7783",
    "nombre": "FUROSEMIDA 20MG/2ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "231056",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7783",
    "nombre": "FUROSEMIDA 20MG/2ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "4300006",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7783",
    "nombre": "FUROSEMIDA 20MG/2ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "4300006",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "OTRO7777",
    "nombre": "GASA 100 YARDAS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "GP10000226",
    "cantidad_principal": 10,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "BOLSA"
  },
  {
    "codigo": "POM10",
    "nombre": "DOLOCOFAMIN 5%",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "007448",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM10",
    "nombre": "DOLOCOFAMIN 5%",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "007875",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM10",
    "nombre": "DOLOCOFAMIN 5%",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6962",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM10",
    "nombre": "DOLOCOFAMIN 5%",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "CN007448",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM11",
    "nombre": "NOVADOL GEL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "007884",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM11",
    "nombre": "NOVADOL GEL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "07737",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM11",
    "nombre": "NOVADOL GEL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7724",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM11",
    "nombre": "NOVADOL GEL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "CN007737",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM11",
    "nombre": "NOVADOL GEL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "CN007737",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM11",
    "nombre": "NOVADOL GEL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "CN008422",
    "cantidad_principal": 4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM12",
    "nombre": "ALIVIOL GEL FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "LG02S2302",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "AYV21",
    "nombre": "TERBOMICINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "1A25014",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 25,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "GENTACOFAR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7047",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIPM8",
    "nombre": "GENTAMICINA 0.3%",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "209454",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "GOTERO"
  },
  {
    "codigo": "MEDIIN-B12",
    "nombre": "GLUCOSA 20% 500 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "212981",
    "cantidad_principal": 3.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 47,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "AYV30",
    "nombre": "GLUCOSA 33%",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "X0969",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 17,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI6696",
    "nombre": "GLUCOSA 50% 500ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "211788",
    "cantidad_principal": 59,
    "unidad_principal": "FRASCO",
    "cantidad_secundaria": 59,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "INSU45",
    "nombre": "GORROS QUIRURGICOS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4129723269",
    "cantidad_principal": 1.3,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 125,
    "unidad_secundaria": "UNIDADES"
  },
  {
    "codigo": "MEDIF",
    "nombre": "PARACETAMOL 100 MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "32545",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "GOTERO"
  },
  {
    "codigo": "MEDI7778",
    "nombre": "LAGRIMAS ARTIFICIALES",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "007719",
    "cantidad_principal": 1,
    "unidad_principal": "FRASCO",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI",
    "nombre": "GUANTES ESTERILES Nº 6 G",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "PROB 0302",
    "cantidad_principal": 10,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 500,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDA2",
    "nombre": "ALFA PERIDOL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "0966",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "GOTERO"
  },
  {
    "codigo": "MEDA2",
    "nombre": "ALFA PERIDOL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "V1336",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "GOTERO"
  },
  {
    "codigo": "MEDA2",
    "nombre": "ALFA PERIDOL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "X0966",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "GOTERO"
  },
  {
    "codigo": "MEDI7813",
    "nombre": "ABRILAR JARABE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25C099B",
    "cantidad_principal": 10,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BLOKTUS NATURAL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6939",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "HIDROCORTIZONA 100",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "11111",
    "cantidad_principal": 3.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 31,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "HIDROCORTIZONA 100",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "250468",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "HIDROCORTIZONA 250",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2407108",
    "cantidad_principal": 1.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 14,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "HIDROCORTIZONA 500",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "250318",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BONAGEL PLUS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7754",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BONAGEL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "5130",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BONAGEL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7871",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI",
    "nombre": "HILO CAT GUT SIMPLE 5/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20300814",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDI",
    "nombre": "HILO CAT GUT SIMPLE 5/0",
    "responsable": "NOELIA TORREZ MAMANI",
    "lote": "20300814(FANNY LUZ YUGAR LEUCA)",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL N 0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20517814",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL N 0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20705321",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 1",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "247097",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 1",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "253740",
    "cantidad_principal": 36,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 36,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 1",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "SJ253740",
    "cantidad_principal": 11,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 3/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20412434",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 3/0",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "7276",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 2/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20622364",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 36,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 2/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "247682",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 4/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "21120642",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 4/0",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "7276",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 19,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 5/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20301714",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 5/0",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20301714",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 6/0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20122723",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 32,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "EQUI7770",
    "nombre": "HILO VICRYL N 0 CON AGUJA 40",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "IA 247101",
    "cantidad_principal": 32,
    "unidad_principal": "PIEZAS",
    "cantidad_secundaria": 32,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDI6765",
    "nombre": "HILO VICRYL Nª 1 AGUJA 40",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2093435",
    "cantidad_principal": 66,
    "unidad_principal": "PIEZAS",
    "cantidad_secundaria": 66,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDI7806",
    "nombre": "HILO POLIPROPILENO 3-0 C/AGUJA MR25 X 75CM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "052023AZC01",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "EQUI7803",
    "nombre": "HILO SEDA 2/0",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "253618",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "EQUI7769",
    "nombre": "HILO SEDA NEGRA 0 CARRETA DE 100 YARDAS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20606782",
    "cantidad_principal": 1,
    "unidad_principal": "CARRETA 100 YADS",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "CARRETA 100 YADS"
  },
  {
    "codigo": "MEDI7828",
    "nombre": "IBUFORT DUO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "C054111",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "IBUPROFENO 400 COMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "42518",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "IBUPROFENO 400 COMP",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "92524",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 59,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDC",
    "nombre": "IBUPROFENO 100",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7234",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDFS2",
    "nombre": "IPSON",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "035103",
    "cantidad_principal": 5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDC",
    "nombre": "IBUPROFENO 200",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7302",
    "cantidad_principal": 6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIB32",
    "nombre": "PIRONAL FORTE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AAXW",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MEBIDOX 400",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "34001",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 24,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "ACTICAP 400",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7583",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDIBB2",
    "nombre": "ACTRON",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ARLMI9",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "CAPSULA BLANDA"
  },
  {
    "codigo": "MEDIBB2",
    "nombre": "ACTRON",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ARMJW6",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "CAPSULA BLANDA"
  },
  {
    "codigo": "MEDIT3",
    "nombre": "IBUPRONAL 400 MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2L24001",
    "cantidad_principal": 0,
    "unidad_principal": "FRASCO",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "CAPSULAS BLANDAS"
  },
  {
    "codigo": "MEDS8",
    "nombre": "NODOL 400",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "X097",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MEBIDOX 600",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "32908",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "ACTICAP 600",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7604",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 47,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDIB30",
    "nombre": "PIRONAL FLU",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "48S",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "EQUI7815",
    "nombre": "EQUIPO DE VENOCLISIS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "IN SUE",
    "cantidad_principal": 36.1,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 903,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIIN-B34",
    "nombre": "INTROCAN Nº 18",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23B13G8261",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 24,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIIN-B34",
    "nombre": "INTROCAN Nº 18",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24M30G8912",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIIN-B35",
    "nombre": "INTROCAN Nº 20",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23G23G8262",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 39,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDI7824",
    "nombre": "ALFA B1",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "X1701",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 25,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDC3",
    "nombre": "ULTRAVIST 300",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "KT0S25J",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDC3",
    "nombre": "ULTRAVIST 300",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "KTOTB57",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI7794",
    "nombre": "ULTRAVIST 300/50ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "KT0PJ4J",
    "cantidad_principal": 7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL IRRIGACION 1000 ML",
    "responsable": "NOELIA TORREZ MAMANI",
    "lote": "212654",
    "cantidad_principal": 2.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 33,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL IRRIGACION 1000 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "212654(NOELIA TORREZ MAMANI)",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "INSU53",
    "nombre": "JERINGA 1 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "12345",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 67,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU53",
    "nombre": "JERINGA 1 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20241019",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 201,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIM5",
    "nombre": "GRIPETIL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23C01",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "COMPRIMIDO RECUBIERTO"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX 100",
    "responsable": "BRIGITTE DUVEYZA VEGA FLORES",
    "lote": "4I7",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 100,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "KETOPROFENO 100",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "30790",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "KETOPROFENO 100",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "32788",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "KETOPROFENO 100",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "32788",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 99,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX B1 B6 B12",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ADJR",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 100,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIB39",
    "nombre": "TALFLEX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ABDS",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLAS"
  },
  {
    "codigo": "MEDIB39",
    "nombre": "TALFLEX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AC4L",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLAS"
  },
  {
    "codigo": "MEDIB39",
    "nombre": "TALFLEX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AC4L",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLAS"
  },
  {
    "codigo": "MEDIB39",
    "nombre": "TALFLEX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AC4L",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLAS"
  },
  {
    "codigo": "MEDIB39",
    "nombre": "TALFLEX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AC4N",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "AMPOLLAS"
  },
  {
    "codigo": "MEDIB39",
    "nombre": "TALFLEX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AHEI",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "AMPOLLAS"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX BI",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "F24ARRK",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DISIDOL 60",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "007662",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DISIDOL 60",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "168",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DISIDOL 60",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "232195",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DISIDOL 60",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "KTI-2501",
    "cantidad_principal": 4.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 42,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "DEXAMINO ORAL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "5873",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDBP",
    "nombre": "L DEXAMINO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6398",
    "cantidad_principal": 1.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDINT20",
    "nombre": "ZOLION RELAX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "MA142",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "LAXUAVE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "30601",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDM",
    "nombre": "CEUMID 500 COMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "M11696",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDM",
    "nombre": "CEUMID 500 COMP",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "M11696",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 15,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDM",
    "nombre": "CEUMID 500",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "M09472",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIPM4",
    "nombre": "LIBBERA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2705424",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "JARABE"
  },
  {
    "codigo": "MEDIC3",
    "nombre": "LEVOFLOXACINA 750",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "001466",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "COMRIMIDO RECUBIERTO"
  },
  {
    "codigo": "AYV",
    "nombre": "LEVOALCOAS IV",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "279-5ABX01",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDB",
    "nombre": "T4",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4EO",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 22,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "EUTIROX 50",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "M45526",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 24,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDI7800",
    "nombre": "EUTIROX 100MCG",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "44214",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 50,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "AYV136",
    "nombre": "LIDOCAINA 1%",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "241114",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 34,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV",
    "nombre": "LIDOCAINA 2% 10",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "30514",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV",
    "nombre": "LIDOCAINA 2% 10",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "35793",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 45,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "TERBOCAINA 2%",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "1B23014-3",
    "cantidad_principal": 14,
    "unidad_principal": "FRASCO",
    "cantidad_secundaria": 14,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "LIDOCAINA 2% 20 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2510134",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIF",
    "nombre": "LIDOCAINA 2% 20 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2510134",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIF",
    "nombre": "LIDOCAINA 2% 20 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "510135",
    "cantidad_principal": 5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIF",
    "nombre": "LIDOCAINA 2% 20 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "B2510132",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "AYV",
    "nombre": "LIDOCAINA 2% 5ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "30010",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV",
    "nombre": "LIDOCAINA 2% 5ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "4240002",
    "cantidad_principal": 1.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 76,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV",
    "nombre": "LIDOCAINA 2% 5ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "424002",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7810",
    "nombre": "TERBOCAINA AMPOLLA INYECTABLE",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "A25010",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 25,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "POM13",
    "nombre": "ROXICAINA JALEA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "250002",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM13",
    "nombre": "ROXICAINA JALEA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "45698",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MEDIIN-B27",
    "nombre": "LINOVERA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "250513",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI7795",
    "nombre": "LIPOFUNDIN 10% FCO 500ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "251528082",
    "cantidad_principal": 5,
    "unidad_principal": "FRASCO",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "HIPOPRES 20",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "29604",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "TABLETA"
  },
  {
    "codigo": "MEDB20",
    "nombre": "DISCOFIX C",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23299041",
    "cantidad_principal": 0.3,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "POM1",
    "nombre": "BONABEN LOCION",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "510195",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIC2",
    "nombre": "LOSARTAN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "004816",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "COMPRIMIDO RECUBIERTO"
  },
  {
    "codigo": "MEDIPM3",
    "nombre": "MAGAL D",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2N04054",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "JARABE"
  },
  {
    "codigo": "MEDIBPH26",
    "nombre": "MAXIBONAGEL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "003633",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MED",
    "nombre": "ATRACURIO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "245",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MED",
    "nombre": "ATRACURIO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ATR245",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MED",
    "nombre": "ATRACURIO",
    "responsable": "DANIELA ALEJANDRA PEÑA VALVERDE",
    "lote": "ATR245",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 50,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIBPH15",
    "nombre": "FLEXICAM B12 FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "3987",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "ESTUCHE UNIDOSIS"
  },
  {
    "codigo": "MEDTC6",
    "nombre": "SUPRACAM 15 AMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "72530",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDA3",
    "nombre": "HUESOBONE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "MEL1022502",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDBP",
    "nombre": "DOLOFLEXICAM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "008048",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDBP",
    "nombre": "DOLOFLEXICAM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "5590",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDF",
    "nombre": "FORTICAM 3B",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6165",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 24,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "AYV",
    "nombre": "FLAMAX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "1A24020",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 25,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "FLEXICAM RELAX SL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7484",
    "cantidad_principal": 2.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 70,
    "unidad_secundaria": "COMPRIMIDO SUBLINGUAL"
  },
  {
    "codigo": "MEDTC5",
    "nombre": "SUPRACAM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "73137",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDTC4",
    "nombre": "SUPRACAM FLEX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "97716",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIB26",
    "nombre": "DIOXADOL G",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "4WJ",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "GOTERO"
  },
  {
    "codigo": "AYV40",
    "nombre": "COFALGINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "007191",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV40",
    "nombre": "COFALGINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "12456",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 77,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV40",
    "nombre": "COFALGINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6356",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV40",
    "nombre": "COFALGINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7189",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DYPIRETIC",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "0000035048",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DYPIRETIC",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "35048",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB",
    "nombre": "GLICENEX 500",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ACOB",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 22,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDB",
    "nombre": "GLICENEX DUO 500/5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4KZ",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 23,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDLQ4",
    "nombre": "METIL PREDGAL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "TP2110125",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIF",
    "nombre": "METOCLOPRAMIDA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "30597",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 93,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "AYV",
    "nombre": "TERMETIK",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "1A24034",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV42",
    "nombre": "METROGYN",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "215-5DX16R",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "AYV42",
    "nombre": "METROGYN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "215-5OX08R",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "AYV42",
    "nombre": "METROGYN",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "5DX16R",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 40,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIIN-B18",
    "nombre": "METRONAC",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24234122B3",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIPM1",
    "nombre": "METROCAPS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "1516919",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDIPM1",
    "nombre": "METROCAPS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "1520107",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 96,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "INSU130",
    "nombre": "MICROPORE 3M 2.5",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "33XLJA",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "EQUI7772",
    "nombre": "MICROPORE 3M 5CMX9,1M",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "33KNJE",
    "cantidad_principal": 18,
    "unidad_principal": "PIEZAS",
    "cantidad_secundaria": 18,
    "unidad_secundaria": "PIES"
  },
  {
    "codigo": "EQUI7772",
    "nombre": "MICROPORE 3M 5CMX9,1M",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "33RKFN",
    "cantidad_principal": 0,
    "unidad_principal": "PIEZAS",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "PIES"
  },
  {
    "codigo": "MED",
    "nombre": "MIDAZOLAM 15 MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "25002",
    "cantidad_principal": 25,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 25,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI7814",
    "nombre": "CLOFENAC RELAX COMPRIMIDO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ABD9",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDI7814",
    "nombre": "CLOFENAC RELAX COMPRIMIDO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "ABDB",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDI7805",
    "nombre": "CLOFENAC RELAX FORTE X CAPSULAS",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AHCA",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "INSU5",
    "nombre": "AGUJA HIPODERMICA Nº 21 G X1 1/2\"",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20250228",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 23,
    "unidad_secundaria": "AGUJA+SACHET"
  },
  {
    "codigo": "INSU4",
    "nombre": "AGUJA HIPODERMICA Nº 21G X1\"",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "202107A",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "AGUJA + SACHET"
  },
  {
    "codigo": "INSU4",
    "nombre": "AGUJA HIPODERMICA Nº 21G X1\"",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "250808",
    "cantidad_principal": 1.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 188,
    "unidad_secundaria": "AGUJA + SACHET"
  },
  {
    "codigo": "INSU73",
    "nombre": "SONDA FOLEY Nº 16 LATEX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25A003",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU73",
    "nombre": "SONDA FOLEY Nº 16 LATEX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "78555",
    "cantidad_principal": 1.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 18,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU73",
    "nombre": "SONDA FOLEY Nº 16 LATEX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "KOM24A0043",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU73",
    "nombre": "SONDA FOLEY Nº 16 LATEX",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "KOM25003",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIN",
    "nombre": "ACUPAM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "D927",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "ACUPAM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "D927",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "ACUPAM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "D927",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "GANEUM 150",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "GAB0022401",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 24,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "NEUROTRAT FORTE 10000",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "29379",
    "cantidad_principal": 1.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DIPIN 20",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "KT4007A",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 96,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDI7802",
    "nombre": "UVAMIN RETARD 100MG",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "080226",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 14,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "INSU70",
    "nombre": "SONDA FOLEY Nº 10 LATEX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "KOM22A0046",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU108",
    "nombre": "VENDA ELASTICA 10 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20324",
    "cantidad_principal": 0.5,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU108",
    "nombre": "VENDA ELASTICA 10 CM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "452366",
    "cantidad_principal": 1,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU31",
    "nombre": "CANULA DE ASPIRACION Nº 12",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20211015",
    "cantidad_principal": 7,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "INSU71",
    "nombre": "SONDA FOLEY Nº 12 LATEX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "22A0046",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU23",
    "nombre": "BRANULA Nº 14",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24A07",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 16,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "INSU32",
    "nombre": "CANULA DE ASPIRACION Nº14",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20230415",
    "cantidad_principal": 35,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 35,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "INSU72",
    "nombre": "SONDA FOLEY Nº 14",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "KOM22A0046",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU80",
    "nombre": "SONDA NASOGASTRICA Nº 14",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "21369",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 19,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU105",
    "nombre": "VENDA DE GASA 15 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20230301",
    "cantidad_principal": 4.1,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 41,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU33",
    "nombre": "CANULA DE ASPIRACION Nº16",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "12345",
    "cantidad_principal": 4,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "INSU33",
    "nombre": "CANULA DE ASPIRACION Nº16",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20241118",
    "cantidad_principal": 0,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "INSU81",
    "nombre": "SONDA NASOGATRICA Nº 16",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23196",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 19,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU25",
    "nombre": "BRANULA Nº 18",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24D25A",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 28,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "INSU25",
    "nombre": "BRANULA Nº 18",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25E11C",
    "cantidad_principal": 3.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 194,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "INSU34",
    "nombre": "CANULA DE ASPIRACION Nº 18",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "202106",
    "cantidad_principal": 2,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "INSU74",
    "nombre": "SONDA FOLEY Nº 18 LATEX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "22A0046",
    "cantidad_principal": 1.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU74",
    "nombre": "SONDA FOLEY Nº 18 LATEX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23A0063",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU82",
    "nombre": "SONDA NASOGASTRICA Nº 18",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "21254",
    "cantidad_principal": 1.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU26",
    "nombre": "BRANULA Nº 20",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24B03C",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "INSU26",
    "nombre": "BRANULA Nº 20",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "25H20B",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 22,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "INSU27",
    "nombre": "BRANULA Nº 22",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24B01H",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "INSU27",
    "nombre": "BRANULA Nº 22",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25H22B",
    "cantidad_principal": 3.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 188,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "INSU7",
    "nombre": "AGUJA HIPODERMICA 23G X 1 1/2",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "13452",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 23,
    "unidad_secundaria": "AGUJA+SACHET"
  },
  {
    "codigo": "INSU7",
    "nombre": "AGUJA HIPODERMICA 23G X 1 1/2",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "221105",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 86,
    "unidad_secundaria": "AGUJA+SACHET"
  },
  {
    "codigo": "INSU7",
    "nombre": "AGUJA HIPODERMICA 23G X 1 1/2",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "250806",
    "cantidad_principal": 1.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 192,
    "unidad_secundaria": "AGUJA+SACHET"
  },
  {
    "codigo": "INSU28",
    "nombre": "BRANULA Nº24",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "000001",
    "cantidad_principal": 47.7,
    "unidad_principal": "ESTUCHE",
    "cantidad_secundaria": 34,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "INSU28",
    "nombre": "BRANULA Nº24",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24G05G8301",
    "cantidad_principal": 0,
    "unidad_principal": "ESTUCHE",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "INSU89",
    "nombre": "TUBO ENDOTRAQUEAL Nº 3",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "221020",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU9",
    "nombre": "AGUJA HIPODERMICA 30G X 1/2\"",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "202208A",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AGUJA+SACHET"
  },
  {
    "codigo": "INSU9",
    "nombre": "AGUJA HIPODERMICA 30G X 1/2\"",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "45678",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 90,
    "unidad_secundaria": "AGUJA+SACHET"
  },
  {
    "codigo": "INSU75",
    "nombre": "SONDA NASOGASTRICA Nº 4",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "22755131",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU90",
    "nombre": "TUBO ENDOTRAQUEAL Nº 4",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20240115",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU92",
    "nombre": "TUBO ENDOTRAQUEAL Nº 5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "22755131",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU93",
    "nombre": "TUBO ENDOTRAQUEAL Nº 5.5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23755133",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU94",
    "nombre": "TUBO ENDOTRAQUEAL Nº 6",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20221008",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU76",
    "nombre": "SONDA NASOGASTRICA Nº 6 O K33",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23755133",
    "cantidad_principal": 2.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 70,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU85",
    "nombre": "TUBO ARMADO N 6.5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2211022333",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU95",
    "nombre": "TUBO ENDOTRAQUEAL Nº 6,5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20240115",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU95",
    "nombre": "TUBO ENDOTRAQUEAL Nº 6,5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20240115",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU50",
    "nombre": "GUANTES ESTERILES Nº 7",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "PROB 0289",
    "cantidad_principal": 15.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 797,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU86",
    "nombre": "TUBO ARMADO Nº 7",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2211022333",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "INSU51",
    "nombre": "GUANTES ESTERILES Nº 7 1/2",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "PROB0290",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU29",
    "nombre": "CANULA DE ASPIRACION Nº 8",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20220310",
    "cantidad_principal": 6,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "INSU29",
    "nombre": "CANULA DE ASPIRACION Nº 8",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20230415",
    "cantidad_principal": 10,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "INSU52",
    "nombre": "GUANTES ESTERILES Nº8",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2022022",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 50,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU77",
    "nombre": "SONDA NASOGASTRICA Nº8",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "22100",
    "cantidad_principal": 3.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 92,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU78",
    "nombre": "SONDA NASOGASTRICA Nº10",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "22102",
    "cantidad_principal": 1.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 37,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU16",
    "nombre": "BISTURI Nº 11",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "202402",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU79",
    "nombre": "SONDA NASOGASTRICA Nº 10",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "22102",
    "cantidad_principal": 1.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 36,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU17",
    "nombre": "BISTURI Nº 15",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "22023",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU49",
    "nombre": "GUANTES ESTERILES Nº 6 1/2",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "PROB 0289",
    "cantidad_principal": 8.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 434,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU97",
    "nombre": "TUBO ENDOTRAQUEAL Nº 8",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "202106",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDIS8",
    "nombre": "NOOPIRAM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "460624",
    "cantidad_principal": 1.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 42,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NORADRENALINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ABW24002",
    "cantidad_principal": 4.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 24,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "INSU59",
    "nombre": "LLAVE DE 3 VIAS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "B24686",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "MEDB7",
    "nombre": "TRACUTIL",
    "responsable": "HAIDEE FUERTES",
    "lote": "25023051",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB7",
    "nombre": "TRACUTIL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25465051",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "TENSIUM 20",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "11250497A",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "TENSIUM 40",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "11250498A",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIM4",
    "nombre": "EUKENE 40",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "22250",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 28,
    "unidad_secundaria": "COMPRIMIDO RECUBIERTO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "OMEGASTRIN 40",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "29138",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIN",
    "nombre": "OMEGASTRIN 20",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "35517",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDI7828",
    "nombre": "OMEPRAZOL 20MG",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "K105184",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 250,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "AYV",
    "nombre": "OMEPRAZOL 40 MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2306328",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "AYV",
    "nombre": "OMEPRAZOL 40 MG",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "250607",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "AYV",
    "nombre": "OMEPRAZOL 40 MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "250611",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDNV",
    "nombre": "ONDANSETRON",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2503",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDNV",
    "nombre": "ONDANSETRON",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ADX24002",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDQ3",
    "nombre": "OSELTA 75",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "251308",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "POM7",
    "nombre": "DERMOTRIZINC",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "29585",
    "cantidad_principal": 2,
    "unidad_principal": "TUBO",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MEDLG2",
    "nombre": "OXITOCINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "241224",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDLG2",
    "nombre": "OXITOCINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "250508",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDLG2",
    "nombre": "OXITOCINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "250580",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIBPH8",
    "nombre": "INHIBID",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2145",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 24,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MED",
    "nombre": "PAPEL DE ELECTROCARDIOGR AMA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7A824",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "ROLLO"
  },
  {
    "codigo": "MEDT",
    "nombre": "PIREDOL 100",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2B23002",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "GOTAS"
  },
  {
    "codigo": "MEDH",
    "nombre": "PARACETAMOL 125 MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7336",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "PARACETAMOL 1GR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "240768",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "PARACETAMOL 1GR",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4D229",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "INSU14",
    "nombre": "BAJALENGUA PEDIATRICO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "202404",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "PALETAS"
  },
  {
    "codigo": "INSU14",
    "nombre": "BAJALENGUA PEDIATRICO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20240421",
    "cantidad_principal": 10,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1000,
    "unidad_secundaria": "PALETAS"
  },
  {
    "codigo": "MEDB",
    "nombre": "PEN DI BEN 2.400.000 U.I",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ABYK",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIT5",
    "nombre": "TERBOCYL FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2308304",
    "cantidad_principal": 7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "VIAL+DILUYENTE +JERINGA+ALCOHOL PAD"
  },
  {
    "codigo": "MEDIT4",
    "nombre": "TERBOCYL 6.3.3",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2308308",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL +DILUYENTE`JERINGA +ALCOHOL PAD"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NOVOPENCIL FORTE 12.6.6",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "BI272",
    "cantidad_principal": 5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 5,
    "unidad_secundaria": "KIT"
  },
  {
    "codigo": "INSU1",
    "nombre": "AGUA OXIGENADA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AF102150425",
    "cantidad_principal": 1.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 19,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "INSU2",
    "nombre": "AGUA OXIGENADA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "AF102150425",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI7821",
    "nombre": "PIPERACICLINA MAS TAZOBACTAM 4,5",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "00227",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI7821",
    "nombre": "PIPERACICLINA MAS TAZOBACTAM 4,5",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "00228",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI7821",
    "nombre": "PIPERACICLINA MAS TAZOBACTAM 4,5",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "123456",
    "cantidad_principal": 1.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDF",
    "nombre": "PIPEBAC T",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2402088",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDF",
    "nombre": "PIPEBAC T",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2408022",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDF",
    "nombre": "PIPEBAC T",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2408022",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDF",
    "nombre": "PIPEBAC T",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2408022",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDF",
    "nombre": "PIPEBAC T",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2408022",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDF",
    "nombre": "PIPEBAC T",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2820244",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDIS4",
    "nombre": "NOOPIRAM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "610925",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIS4",
    "nombre": "NOOPIRAM",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "610925",
    "cantidad_principal": 1.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 9,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "POM3",
    "nombre": "QUEMACURAN L",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "0000035093",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM3",
    "nombre": "QUEMACURAN L",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "31272",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MEDI6690",
    "nombre": "FLOGIATRIN POMADA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2045525",
    "cantidad_principal": 4,
    "unidad_principal": "FRASCO",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "INSU63",
    "nombre": "PORTAOBJETO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "12345",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 14,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDS10",
    "nombre": "PREDNISONA LCH 5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "E012529267",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDI7820",
    "nombre": "PRESTAT 75",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "K24ASVS",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 40,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "GANEUM 75",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "GABOO32501",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MEDC",
    "nombre": "PREGABALINA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6380",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDC",
    "nombre": "PREGABALINA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "CN007669",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDQ5",
    "nombre": "PREBALIN 75",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "250570",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "COMPRIMIDO TRICEPTADO"
  },
  {
    "codigo": "MED",
    "nombre": "PRESERVATIVO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "452843",
    "cantidad_principal": 32,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 96,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDF",
    "nombre": "DORFLEX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6089",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDF",
    "nombre": "DORFLEX AMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "5337",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "MEDB",
    "nombre": "BAGO VITAL DIGEST",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2501053",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DEMOTIL AMPOLLA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "25333",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIIN-B26",
    "nombre": "PROPOFOL LIPURO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "25094050",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIIN-B26",
    "nombre": "PROPOFOL LIPURO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25094050",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIIN-B26",
    "nombre": "PROPOFOL LIPURO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25094050",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIIN-B26",
    "nombre": "PROPOFOL LIPURO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25173050",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIIN-B26",
    "nombre": "PROPOFOL LIPURO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25173050",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIIN-B26",
    "nombre": "PROPOFOL LIPURO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "25173050",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "QUETOROL 30 SL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "31051",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "COMPRIMIDO SUBLINGUAL"
  },
  {
    "codigo": "MEDIN",
    "nombre": "QUETOROL 30MG",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "33141",
    "cantidad_principal": 1.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 34,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "QUETOROL 30 AMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ACF23005",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "QUETOROL 30 AMP",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "AKK25001",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "QUETOROL 30 AMP",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "PY089",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "QUETOROL 30 AMP",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "PY089",
    "cantidad_principal": 3.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 38,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDS4",
    "nombre": "SALBUTAMOL AEROSOL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20401831",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AEROSOL"
  },
  {
    "codigo": "MEDS4",
    "nombre": "SALBUTAMOL AEROSOL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "240941",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AEROSOL"
  },
  {
    "codigo": "MEDI7822",
    "nombre": "GELBRONQUIAL",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "2A24017",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIPM7",
    "nombre": "BUTAMOL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "04143",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "GOTERO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DEHIDROLIT 75",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "33515",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "FRASCO3",
    "nombre": "DEHIDROLIT S 75",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "34136",
    "cantidad_principal": 3,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "FRASCO1",
    "nombre": "HIDRATA ABD 75",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "6200004",
    "cantidad_principal": 1,
    "unidad_principal": "FRASCO",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "FRASCO5",
    "nombre": "CURADIL 90",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "165-X36",
    "cantidad_principal": 4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDF",
    "nombre": "SEVOFLURANO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2501053104",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDF",
    "nombre": "SEVOFLURANO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2506263102",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SIDERAL ORO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "3094",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 15,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDF",
    "nombre": "GASSTOP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6023",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 24,
    "unidad_secundaria": "COMPRIMIDO MASTICABLE"
  },
  {
    "codigo": "MEDIPM2",
    "nombre": "EVIGAX FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "1537131",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "CAPSULA BLANDA"
  },
  {
    "codigo": "MEDIBPH9",
    "nombre": "DIGESTOGAS 300",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7543",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 30,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "INSU165",
    "nombre": "HILO SEDA NEGRA 0",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "21205903",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MEDBP",
    "nombre": "SIGNUM M",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7467",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 19,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL GLUCOSA 10% 500 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24387",
    "cantidad_principal": 5.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 69,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MED",
    "nombre": "SOL FISIOLOGICA 100ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "345612",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 27,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MED",
    "nombre": "SOL FISIOLOGICA 1000ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "0000035691",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MED",
    "nombre": "SOL FISIOLOGICA 1000ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "35690",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MED",
    "nombre": "SOL FISIOLOGICA 1000ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "36555",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MED",
    "nombre": "SOL FISIOLOGICA 1000ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "36577",
    "cantidad_principal": 5.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 70,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MED",
    "nombre": "ENEMA VIT",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "508474",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "SOL",
    "nombre": "GLUCOSA 5% 500 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "212954",
    "cantidad_principal": 1.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 14,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDF",
    "nombre": "SOL GLUCOSA 50% 500 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "211788",
    "cantidad_principal": 4.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 59,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL RINGER NORMAL 1000",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "30500",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL RINGER NORMAL 1000",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "36292",
    "cantidad_principal": 4.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 59,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDI",
    "nombre": "SOL RINGER NORMAL 500",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "211810",
    "cantidad_principal": 17.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 215,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL RINGER LACTATO 500 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "210972",
    "cantidad_principal": 26,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 312,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL RINGER LACTATO 1000ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "30622",
    "cantidad_principal": 1.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 16,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL RINGER LACTATO 1000ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "37972",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 36,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL GLUCOSA 10% 1000ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "208279",
    "cantidad_principal": 3.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 39,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL GLUCOSA 10% 1000ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "213758",
    "cantidad_principal": 9.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 119,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIIN-B2",
    "nombre": "SOLUCION FISIOLOGICA 500 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "31135",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIIN-B2",
    "nombre": "SOLUCION FISIOLOGICA 500 ML",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "37436",
    "cantidad_principal": 4.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 57,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL GLUCOSA 5% 1000 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "17028",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL GLUCOSA 5% 1000 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "25673",
    "cantidad_principal": 8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 96,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDIIN-B14",
    "nombre": "MANITOL 500 ML",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "211966",
    "cantidad_principal": 14,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 168,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "INSU122",
    "nombre": "SONDA NELATON Nº 16",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "121",
    "cantidad_principal": 2,
    "unidad_principal": "SACHET",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIIN-B39",
    "nombre": "SPINOCAN Nº 22",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24C26H8B02",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 8,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIIN-B40",
    "nombre": "SPINOCAN Nº 25",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "24H09H8B01",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIIN-B40",
    "nombre": "SPINOCAN Nº 25",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "24H09H8B01",
    "cantidad_principal": 0.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIIN-B41",
    "nombre": "SPINOCAN Nº 26",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23K04H8B04",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDH",
    "nombre": "ECHINACEA COMPLEX",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "04401",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MED",
    "nombre": "HEMOVAC Nº 14",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4164224H",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 2,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MED",
    "nombre": "HEMOVAC Nº 16",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "4176024K",
    "cantidad_principal": 4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDM",
    "nombre": "SUCRALTIP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2106114",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDBP",
    "nombre": "SUCRABONAGEL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "7434",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDA",
    "nombre": "SUERO DE LA VIDA",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "T1710",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 44,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDINT13",
    "nombre": "COTRIZOL FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "31280",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "AYV49",
    "nombre": "SULFATO DE MAGNESIO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "32093",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV49",
    "nombre": "SULFATO DE MAGNESIO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "34664",
    "cantidad_principal": 0.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 11,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV49",
    "nombre": "SULFATO DE MAGNESIO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "34664",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 48,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SUMAX 50",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "30524",
    "cantidad_principal": 2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 4,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "GLUCONATO DE CALCIO 10%",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2505119",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "GLUCONATO DE CALCIO 10%",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "259123",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "GLUCONATO DE CALCIO 10%",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "259127",
    "cantidad_principal": 0.1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "INSU47",
    "nombre": "GUANTES DE LATEX M",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "8735",
    "cantidad_principal": 17.4,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 870,
    "unidad_secundaria": "PARES"
  },
  {
    "codigo": "INSU46",
    "nombre": "GUANTES DE LATEX S",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "45687",
    "cantidad_principal": 6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 300,
    "unidad_secundaria": "PARES"
  },
  {
    "codigo": "MEDB14",
    "nombre": "STOPER",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "23178171",
    "cantidad_principal": 0.5,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 25,
    "unidad_secundaria": "ESTUCHE"
  },
  {
    "codigo": "MED",
    "nombre": "TERMOMETRO DE MERCURIO",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "13597",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 7,
    "unidad_secundaria": "TERMOMETRO"
  },
  {
    "codigo": "MED",
    "nombre": "TERMOMETRO DE MERCURIO",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20230822",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 12,
    "unidad_secundaria": "TERMOMETRO"
  },
  {
    "codigo": "MED",
    "nombre": "GLUCOCINTAS",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "250612A4",
    "cantidad_principal": 12,
    "unidad_principal": "FRASCO",
    "cantidad_secundaria": 600,
    "unidad_secundaria": "TIRAS"
  },
  {
    "codigo": "MEDIPM9",
    "nombre": "TOBRAZOL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "201064",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "GOTERO"
  },
  {
    "codigo": "MEDF",
    "nombre": "TAMBOL COMP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "T03X01",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 20,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "TAMBOL",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ABR24009",
    "cantidad_principal": 3.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 32,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDB16",
    "nombre": "TRANSOFIX N",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "22A14LA023",
    "cantidad_principal": 2.2,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 112,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "MEDIB 25",
    "nombre": "BACTICEL FORTE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "ABSV",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "COMPRIMIDO RECUBIERTO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "TROXOLINA CAP",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20833",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 16,
    "unidad_secundaria": "CAPSULA"
  },
  {
    "codigo": "MED",
    "nombre": "TUBO DE TRAQUEOSTOMIA 7.5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6117501",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MED",
    "nombre": "TUBO DE TRAQUEOSTOMIA 7",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "6122098",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "EQUI6767",
    "nombre": "TUBO ENDOTRAQUEAL Nª 7.0 CON BALON",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "20221008",
    "cantidad_principal": 6,
    "unidad_principal": "PIEZAS",
    "cantidad_secundaria": 6,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "EQUI6769",
    "nombre": "TUBO ENDOTRAQUEAL Nª7.5 CON BALON",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20240115",
    "cantidad_principal": 13,
    "unidad_principal": "PIEZAS",
    "cantidad_secundaria": 13,
    "unidad_secundaria": "PIEZAS"
  },
  {
    "codigo": "MED",
    "nombre": "TUBO ENDOTRAQUEAL Nº 2.5",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "221020",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "POM23",
    "nombre": "CLORANFENICOL 1%",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "044975",
    "cantidad_principal": 3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 3,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM23",
    "nombre": "CLORANFENICOL 1%",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "113164",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "POM23",
    "nombre": "CLORANFENICOL 1%",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "X044975",
    "cantidad_principal": 10,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 10,
    "unidad_secundaria": "TUBO"
  },
  {
    "codigo": "MEDI7797",
    "nombre": "VALAX 160MG",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "S086964",
    "cantidad_principal": 0.9,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 28,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MEDI7798",
    "nombre": "VALAXAM D",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "S087034",
    "cantidad_principal": 0.8,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 25,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "MED",
    "nombre": "VENDA DE GASA 20 CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "202403",
    "cantidad_principal": 18,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 90,
    "unidad_secundaria": "SACHET"
  },
  {
    "codigo": "INSU6759",
    "nombre": "VENDAS DE GASA 10CM",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "H11320825",
    "cantidad_principal": 36,
    "unidad_principal": "BOLSA",
    "cantidad_secundaria": 36,
    "unidad_secundaria": "BOLSA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "VANCOMICINA 500",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "25746",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "VIAL"
  },
  {
    "codigo": "MEDI7782",
    "nombre": "MAGNESIO VIMIN",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "34490",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDI7782",
    "nombre": "MAGNESIO VIMIN",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "36521",
    "cantidad_principal": 0.6,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 35,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDINT30",
    "nombre": "ACD VIMIN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "32468",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "GOTERO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "B VIMIN 300",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "20947",
    "cantidad_principal": 0.7,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 14,
    "unidad_secundaria": "COMPRIMIDO"
  },
  {
    "codigo": "AYV51",
    "nombre": "VITAMINA C",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "35804",
    "cantidad_principal": 0.3,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 34,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV51",
    "nombre": "VITAMINA C",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "X1574",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDT",
    "nombre": "VITAMINA C 2 SOBRE",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "2S24004-4",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 25,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDIV",
    "nombre": "C VIMIN + ZINC",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "28165",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "SOBRES"
  },
  {
    "codigo": "MEDI6764",
    "nombre": "VITAMINA K MUNDO PHARMA",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "240618",
    "cantidad_principal": 45,
    "unidad_principal": "AMPOLLA",
    "cantidad_secundaria": 45,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "AYV",
    "nombre": "VITAMINA K",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "250490",
    "cantidad_principal": 0,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 0,
    "unidad_secundaria": "AMPOLLA"
  },
  {
    "codigo": "MEDI",
    "nombre": "ZINC VIMIN",
    "responsable": "FANNY LUZ YUGAR LEUCA",
    "lote": "31376",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 1,
    "unidad_secundaria": "FRASCO"
  },
  {
    "codigo": "MEDFS10",
    "nombre": "SOMNO XR",
    "responsable": "PAOLA FRANCHESKA FLORES CRUZ",
    "lote": "099933",
    "cantidad_principal": 1,
    "unidad_principal": "CAJA",
    "cantidad_secundaria": 29,
    "unidad_secundaria": "COMPRIMIDO"
  }
]
JSON, true);

        $now = now();
        $productoIds = DB::table('productos')
            ->get(['id', 'codigo', 'nombre'])
            ->mapWithKeys(fn ($producto) => [$producto->codigo.'|'.$producto->nombre => $producto->id])
            ->all();

        $unidadIds = DB::table('unidades')->pluck('id', 'nombre')->all();
        $rows = [];

        foreach ($inventarios as $inventario) {
            $productoId = $productoIds[$inventario['codigo'].'|'.$inventario['nombre']] ?? null;
            if (!$productoId) {
                continue;
            }

            $rows[] = [
                'producto_id' => $productoId,
                'responsable' => $inventario['responsable'],
                'lote' => $inventario['lote'],
                'cantidad_principal' => $inventario['cantidad_principal'],
                'unidad_principal_id' => $unidadIds[$inventario['unidad_principal']] ?? null,
                'cantidad_secundaria' => $inventario['cantidad_secundaria'],
                'unidad_secundaria_id' => $unidadIds[$inventario['unidad_secundaria']] ?? null,
                'origen_archivo' => 'INVENTARIO FARMACIA (1).xlsx',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('producto_inventarios')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_inventarios');
    }
};
