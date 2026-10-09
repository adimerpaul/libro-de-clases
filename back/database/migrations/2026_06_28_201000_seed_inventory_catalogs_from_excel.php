<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $fabricantes = json_decode(<<<'JSON'
[
  "3M",
  "3M/",
  "ABD/GOBBI",
  "ALCOA",
  "ALCOS",
  "ALFA",
  "ALFA DISMEDIN",
  "ARGENTINO",
  "BAGO",
  "BAGO BAYER",
  "BAYER",
  "BLANCO Y NEGRO",
  "BQS",
  "BRAUN",
  "BRESKOT PHARMA",
  "CATEDRAL",
  "CHANNELMED/SU RGICAL",
  "CHINO",
  "CLINICAL",
  "COFAR",
  "COVIDIEN",
  "CREMER",
  "CREMER/CHINO",
  "CRISTALIA",
  "DESC",
  "DISMEDIN",
  "EQUISPON",
  "ETHICON",
  "EUROFARMA",
  "FARMEDICAL",
  "FORTIER",
  "GLOBUS",
  "GOBBI NOVAG",
  "HAHNEMANN",
  "HEMC",
  "HERSIL",
  "IFA",
  "INTI",
  "INTI PHARMANDINA",
  "INTI VIMIN",
  "INTI- BRAUN",
  "INTI- BRAUN DISMEDIN",
  "JOSA",
  "LAB ABD",
  "LAQFAGAL",
  "LARJAN",
  "LCH",
  "LIBRA",
  "MEDPRIN",
  "MEGALABS",
  "MUNDO PHARMA",
  "NIPRO",
  "NOVO PHARMA",
  "OPTIMED",
  "PHARMANDINA",
  "POLYMED",
  "PREMIER",
  "PREMIER/OPTIME D",
  "PREMIER/SANJET MEDICAL",
  "PREMIER/SENCIC ARE",
  "PREMIER7DEX MED",
  "PRO MEDICAL",
  "PROBIN",
  "QUIMFA",
  "ROPSHON",
  "SAE",
  "SALUR",
  "SAMED",
  "SAMED/WELLEAD/ INDOTRACHEAL TUBE",
  "SAN FERNANDO",
  "SAVAL",
  "SIGMA",
  "SILFAB",
  "SUTUMED/BYOLIN E/SUTURES VITALIS",
  "TAMIVA",
  "TECNOFARMA",
  "TELCHI LITEL",
  "TERBOL",
  "TES/PREMIER",
  "TEXABOL/PREMIE R",
  "UNILENE",
  "VITA",
  "WELLEAD",
  "WINMED/NEOVAC"
]
JSON, true);

        $unidades = json_decode(<<<'JSON'
[
  {
    "nombre": "AEROSOL",
    "abreviatura": "AEROSOL"
  },
  {
    "nombre": "AGUJA",
    "abreviatura": "AGUJA"
  },
  {
    "nombre": "AGUJA + SACHET",
    "abreviatura": "AGUJA + SACHET"
  },
  {
    "nombre": "AGUJA+SACHET",
    "abreviatura": "AGUJA+SACHET"
  },
  {
    "nombre": "AMPOLLA",
    "abreviatura": "AMPOLLA"
  },
  {
    "nombre": "AMPOLLA + SOLVENTE",
    "abreviatura": "AMPOLLA + SOLVENTE"
  },
  {
    "nombre": "AMPOLLA ORAL",
    "abreviatura": "AMPOLLA ORAL"
  },
  {
    "nombre": "AMPOLLAS",
    "abreviatura": "AMPOLLAS"
  },
  {
    "nombre": "BLISTER",
    "abreviatura": "BLISTER"
  },
  {
    "nombre": "BOLSA",
    "abreviatura": "BOLSA"
  },
  {
    "nombre": "CAJA",
    "abreviatura": "CAJA"
  },
  {
    "nombre": "CAJA DISPENSADORA",
    "abreviatura": "CAJA DISPENSADORA"
  },
  {
    "nombre": "CAJA X 2 AMP",
    "abreviatura": "CAJA X 2 AMP"
  },
  {
    "nombre": "CAPSULA",
    "abreviatura": "CAPSULA"
  },
  {
    "nombre": "CAPSULA BLANDA",
    "abreviatura": "CAPSULA BLANDA"
  },
  {
    "nombre": "CAPSULAS",
    "abreviatura": "CAPSULAS"
  },
  {
    "nombre": "CAPSULAS BLANDAS",
    "abreviatura": "CAPSULAS BLANDAS"
  },
  {
    "nombre": "CARRETA",
    "abreviatura": "CARRETA"
  },
  {
    "nombre": "CARRETA 100 YADS",
    "abreviatura": "CARRETA 100 YADS"
  },
  {
    "nombre": "CARRETE",
    "abreviatura": "CARRETE"
  },
  {
    "nombre": "COLLAR",
    "abreviatura": "COLLAR"
  },
  {
    "nombre": "COMPRIMIDO",
    "abreviatura": "COMPRIMIDO"
  },
  {
    "nombre": "COMPRIMIDO MASTICABLE",
    "abreviatura": null
  },
  {
    "nombre": "COMPRIMIDO RECUBIERTO",
    "abreviatura": null
  },
  {
    "nombre": "COMPRIMIDO SUBLINGUAL",
    "abreviatura": null
  },
  {
    "nombre": "COMPRIMIDO TRICEPTADO",
    "abreviatura": null
  },
  {
    "nombre": "COMRIMIDO RECUBIERTO",
    "abreviatura": "COMRIMIDO RECUBIERTO"
  },
  {
    "nombre": "ESPECULO",
    "abreviatura": "ESPECULO"
  },
  {
    "nombre": "ESTUCHE",
    "abreviatura": "ESTUCHE"
  },
  {
    "nombre": "ESTUCHE UNIDOSIS",
    "abreviatura": "ESTUCHE UNIDOSIS"
  },
  {
    "nombre": "FRASCO",
    "abreviatura": "FRASCO"
  },
  {
    "nombre": "GOTAS",
    "abreviatura": "GOTAS"
  },
  {
    "nombre": "GOTERO",
    "abreviatura": "GOTERO"
  },
  {
    "nombre": "JARABE",
    "abreviatura": "JARABE"
  },
  {
    "nombre": "JERINGA PRELLENADA",
    "abreviatura": "JERINGA PRELLENADA"
  },
  {
    "nombre": "JERINGA PRERELLENADA",
    "abreviatura": "JERINGA PRERELLENADA"
  },
  {
    "nombre": "KIT",
    "abreviatura": "KIT"
  },
  {
    "nombre": "PACK",
    "abreviatura": "PACK"
  },
  {
    "nombre": "PALETAS",
    "abreviatura": "PALETAS"
  },
  {
    "nombre": "PAR",
    "abreviatura": "PAR"
  },
  {
    "nombre": "PAR DE AMPOLLAS",
    "abreviatura": "PAR DE AMPOLLAS"
  },
  {
    "nombre": "PARES",
    "abreviatura": "PARES"
  },
  {
    "nombre": "PIES",
    "abreviatura": "PIES"
  },
  {
    "nombre": "PIEZAS",
    "abreviatura": "PIEZAS"
  },
  {
    "nombre": "ROLLO",
    "abreviatura": "ROLLO"
  },
  {
    "nombre": "ROLLOS",
    "abreviatura": "ROLLOS"
  },
  {
    "nombre": "SACHET",
    "abreviatura": "SACHET"
  },
  {
    "nombre": "SACHET + AGUJA",
    "abreviatura": "SACHET + AGUJA"
  },
  {
    "nombre": "SOBRE PLASTICO",
    "abreviatura": "SOBRE PLASTICO"
  },
  {
    "nombre": "SOBRES",
    "abreviatura": "SOBRES"
  },
  {
    "nombre": "SPRAY",
    "abreviatura": "SPRAY"
  },
  {
    "nombre": "TABLETA",
    "abreviatura": "TABLETA"
  },
  {
    "nombre": "TABLETA MASTTICABLE",
    "abreviatura": "TABLETA MASTTICABLE"
  },
  {
    "nombre": "TABLETAS",
    "abreviatura": "TABLETAS"
  },
  {
    "nombre": "TERMOMETRO",
    "abreviatura": "TERMOMETRO"
  },
  {
    "nombre": "TIRAS",
    "abreviatura": "TIRAS"
  },
  {
    "nombre": "TUBO",
    "abreviatura": "TUBO"
  },
  {
    "nombre": "UNIDADES",
    "abreviatura": "UNIDADES"
  },
  {
    "nombre": "VIAL",
    "abreviatura": "VIAL"
  },
  {
    "nombre": "VIAL + JERINGA",
    "abreviatura": "VIAL + JERINGA"
  },
  {
    "nombre": "VIAL +DILUYENTE`JERINGA +ALCOHOL PAD",
    "abreviatura": null
  },
  {
    "nombre": "VIAL+AMPOLLA +JERINGA",
    "abreviatura": null
  },
  {
    "nombre": "VIAL+DILUYENTE +JERINGA+ALCOHOL PAD",
    "abreviatura": null
  },
  {
    "nombre": "VIAL+SOLVENTE +JERINGA",
    "abreviatura": null
  },
  {
    "nombre": "VIALES",
    "abreviatura": "VIALES"
  }
]
JSON, true);

        $productos = json_decode(<<<'JSON'
[
  {
    "codigo": "MEDI7813",
    "nombre": "ABRILAR JARABE",
    "descripcion": "HEDERA HELIX",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDINT30",
    "nombre": "ACD VIMIN",
    "descripcion": "VITAMINA A C Y D",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "GOTERO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIC7",
    "nombre": "ACETILCISTEINA 200",
    "descripcion": "ACETILCISTEINA 200 MG MUCOLITICO FLUDIFICANTE ANTIOXIDANTE SABOR NARANJA",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7773",
    "nombre": "ACETILCISTEINA 300MG/3ML AMPOLLA",
    "descripcion": "ACETILCISTEINA 300MG/3ML AMPOLLA",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ACICLOVIR 400",
    "descripcion": "ACICLOVIR 400 MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "ACTICAP 400",
    "descripcion": "IBUPROFENO 400 MG",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "ACTICAP 600",
    "descripcion": "IBUPROFENO 600 MG",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBB2",
    "nombre": "ACTRON",
    "descripcion": "IBUPROFENO 400 MG ANALGESICO ANTIINFLAMATORIO ANTIFEBRIL",
    "marca": "BAGO BAYER",
    "fabricante": "BAGO BAYER",
    "unidad": "CAPSULA BLANDA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "ACUPAM",
    "descripcion": "NEFOPAM 20 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7771",
    "nombre": "ADECUAN AMPOLLA",
    "descripcion": "ADECUAN AMPOLLA",
    "marca": "SIGMA",
    "fabricante": "SIGMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ADRENALINA",
    "descripcion": "ADRENALINA",
    "marca": "INTI PHARMANDINA",
    "fabricante": "INTI PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU115",
    "nombre": "AEROCAMARA",
    "descripcion": "ADULTO",
    "marca": "SILFAB",
    "fabricante": "SILFAB",
    "unidad": "ESTUCHE",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDM9",
    "nombre": "AERONID 50",
    "descripcion": "ANTIASMATICO",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "AEROSOL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU1",
    "nombre": "AGUA OXIGENADA",
    "descripcion": "PEROXIDO DE HIDROGENO 1 LT",
    "marca": "TELCHI LITEL",
    "fabricante": "TELCHI LITEL",
    "unidad": "FRASCO",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU2",
    "nombre": "AGUA OXIGENADA",
    "descripcion": "PEROXIDO DE HIDROGENO 100 ML",
    "marca": "TELCHI LITEL",
    "fabricante": "TELCHI LITEL",
    "unidad": "FRASCO",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "AYV",
    "nombre": "AGUA PARA INYECCION",
    "descripcion": "AGUA ESTERIL 5 ML",
    "marca": "PHARMANDINA",
    "fabricante": "PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU6",
    "nombre": "AGUJA HIPODERMICA",
    "descripcion": "22GX1\"",
    "marca": "PREMIER/DEX MED",
    "fabricante": "PREMIER7DEX MED",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "EQUI6770",
    "nombre": "AGUJA HIPODERMICA 22G 1/2",
    "descripcion": "AGUJA HIPODERMICA 22G 1/2",
    "marca": "OPTIMED",
    "fabricante": "OPTIMED",
    "unidad": "SACHET",
    "tipo": "EQUIPAMIENTO"
  },
  {
    "codigo": "MEDI7804",
    "nombre": "AGUJA HIPODERMICA 23G OPTIMED",
    "descripcion": "AGUJA HIPODERMICA 23G OPTIMED",
    "marca": "GLOBUS",
    "fabricante": "GLOBUS",
    "unidad": "AGUJA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU7",
    "nombre": "AGUJA HIPODERMICA 23G X 1 1/2",
    "descripcion": "Nº 23GX1 1/2\"",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "AGUJA+SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU9",
    "nombre": "AGUJA HIPODERMICA 30G X 1/2\"",
    "descripcion": "Nº 30G X 1/2\"",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "AGUJA+SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU3",
    "nombre": "AGUJA HIPODERMICA Nº 18G X1 1/2\"",
    "descripcion": "AGUJA",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "SACHET + AGUJA",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU5",
    "nombre": "AGUJA HIPODERMICA Nº 21 G X1 1/2\"",
    "descripcion": "N 21 GX 1 1/2\"",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "AGUJA+SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU4",
    "nombre": "AGUJA HIPODERMICA Nº 21G X1\"",
    "descripcion": "N 21G X 1\"",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "AGUJA + SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ALBENDAZOL 200",
    "descripcion": "ALBENDAZOL 200 MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "COMPRIMIDO MASTICABLE",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7799",
    "nombre": "ALBUMINA HUMANA 20%",
    "descripcion": "ALBUMINA HUMANA 20%",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDM",
    "nombre": "ALBURX",
    "descripcion": "ALBUMINA HUMANA 20%",
    "marca": "DESC",
    "fabricante": "DESC",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7824",
    "nombre": "ALFA B1",
    "descripcion": "INYECTABLE",
    "marca": "ALFA",
    "fabricante": "ALFA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDA2",
    "nombre": "ALFA PERIDOL",
    "descripcion": "HALOPERIDOL 2MG/ML",
    "marca": "ALFA",
    "fabricante": "ALFA",
    "unidad": "GOTERO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU11",
    "nombre": "ALGODON 100 GR",
    "descripcion": "ALGODON 100 GR",
    "marca": "TEXABOL/PREMIER",
    "fabricante": "TEXABOL/PREMIE R",
    "unidad": "ROLLO",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MED",
    "nombre": "ALGODON 400 GR",
    "descripcion": "400 MG",
    "marca": "PREMIER",
    "fabricante": "PREMIER",
    "unidad": "ROLLOS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "ALIVIOL ANTIGRIPAL CAP COLORES",
    "descripcion": "ATIGRIPAL COLORES",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "POM12",
    "nombre": "ALIVIOL GEL FORTE",
    "descripcion": "GEL 2%",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "ALIVIOL PLUS",
    "descripcion": "ALIVIOL PLUS",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIIN-B24",
    "nombre": "AMINOPLASMAL",
    "descripcion": "AMINOPLASMAL",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDA",
    "nombre": "AMIODORONA",
    "descripcion": "150 MG",
    "marca": "LARJAN",
    "fabricante": "LARJAN",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI8",
    "nombre": "AMITRIPTILINA",
    "descripcion": "25 MG ANTIDEPRESIVO",
    "marca": "SAE",
    "fabricante": "SAE",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "AMLODIPINA 10",
    "descripcion": "10 MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDFS1",
    "nombre": "AMOVAL",
    "descripcion": "AMOXICILINA 500MG/5 ML 100 ML SUSPENSION",
    "marca": "SAVAL",
    "fabricante": "SAVAL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDC",
    "nombre": "AMOXICILINA 1",
    "descripcion": "AMOXICILINA 1000 MG",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDC",
    "nombre": "AMOXICILINA 500 MG",
    "descripcion": "AMOXICILINA 500 MG /5 ML",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "AMOXIDIN 250",
    "descripcion": "AMOXICILINA 250 MG",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "AMOXIDIN 500",
    "descripcion": "AMOXICILINA 500 MG",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "AMOXIDIN PLUS FORTE",
    "descripcion": "AMOXICILINA 875 ACIDO CLAVULANICO 125",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "AMPICILINA",
    "descripcion": "AMPICILINA BASE 1000 MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDPR9",
    "nombre": "ANESTEARS",
    "descripcion": "CLORHIDRATO DE PROXIMETACAINA 0.5%",
    "marca": "PRO MEDICAL",
    "fabricante": "PRO MEDICAL",
    "unidad": "GOTAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "ANTIGRIPAL COMPUESTO",
    "descripcion": "ANTIGRIPAL",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ASA",
    "descripcion": "ACIDO ACETILSALICILICO 100",
    "marca": "INTI PHARMANDINA",
    "fabricante": "INTI PHARMANDINA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "ATORVASTATINA",
    "descripcion": "20 MG",
    "marca": "DISMEDIN",
    "fabricante": "DISMEDIN",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "ATRACURIO",
    "descripcion": "MED",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV5",
    "nombre": "ATROPINA",
    "descripcion": "ATROPINA SULFATO 1MG/1ML",
    "marca": "ALFA",
    "fabricante": "ALFA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDH",
    "nombre": "AZITROMICINA 1",
    "descripcion": "1 GR",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "B VIMIN 300",
    "descripcion": "VITAMINA B",
    "marca": "INTI PHARMANDINA",
    "fabricante": "INTI PHARMANDINA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIFA",
    "nombre": "BACITRACINA NEOMICINA",
    "descripcion": "ANTIBIOTICO",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB 25",
    "nombre": "BACTICEL FORTE",
    "descripcion": "TRIMETOPRIMA 160 MG SULFAMETOXAZOL 800 MG ANTIBIOTICO BACTERICIDA DE AMPLIO ASPECTRO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO RECUBIERTO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7788",
    "nombre": "BACTICEL FORTE SUSPENSION",
    "descripcion": "ANTIBIOTICO BACTERICIDA",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7789",
    "nombre": "BACTICEL SUSPENSION",
    "descripcion": "ANTIBIOTICO BACTERICIDA",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "BAGO VITAL DIGEST",
    "descripcion": "PROBIOTICOS",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU13",
    "nombre": "BAJALENGUA ADULTO",
    "descripcion": "BAJALENGUA",
    "marca": "OPTIMED",
    "fabricante": "OPTIMED",
    "unidad": "PALETAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU14",
    "nombre": "BAJALENGUA PEDIATRICO",
    "descripcion": "PEDIATRICO",
    "marca": "OPTIMED",
    "fabricante": "OPTIMED",
    "unidad": "PALETAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BECOR RAPILENTO",
    "descripcion": "BETAMETASONA FOSFATO 6 MG BETAMETASONA ACETATO 6 MG",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "BETACLOX 1 GR",
    "descripcion": "CLOXACILINA 1 GR",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7776",
    "nombre": "BETISTIN",
    "descripcion": "BETISTIN 16MG",
    "marca": "EUROFARMA",
    "fabricante": "EUROFARMA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV5",
    "nombre": "BICARBONATO DE SODIO",
    "descripcion": "BICARBONATO DE SODIO 800MG/10ML",
    "marca": "PHARMANDINA",
    "fabricante": "PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "BICERTO 150",
    "descripcion": "150 MG",
    "marca": "EUROFARMA",
    "fabricante": "EUROFARMA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN6",
    "nombre": "BILISAN 100",
    "descripcion": "ACIDO DEHIDROCOLICO 100 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "TABLETAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "BIO ELECTRO",
    "descripcion": "BIO ELECTRO",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDS6",
    "nombre": "BIOCICATRIZANTE",
    "descripcion": "BIOCICATRIZANTE",
    "marca": "SAE",
    "fabricante": "SAE",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBPH7",
    "nombre": "BIOTIX",
    "descripcion": "AMOXICILINA 875 MG + ACIDO CLAVULANICO 125 MG ANTIBITICO DUAL",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "COMPRIMIDO RECUBIERTO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU16",
    "nombre": "BISTURI Nº 11",
    "descripcion": "Nº11",
    "marca": "CHANNELMED/SUR GICAL",
    "fabricante": "CHANNELMED/SU RGICAL",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU17",
    "nombre": "BISTURI Nº 15",
    "descripcion": "Nº15",
    "marca": "CHANNELMED/SUR GICAL",
    "fabricante": "CHANNELMED/SU RGICAL",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BLOKTUS NATURAL",
    "descripcion": "HEDERA HELIX 35MG/5ML 120ML MUCOLITICO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB22",
    "nombre": "BOLSA DE COLOSTOMIA",
    "descripcion": "BOLSA DE COLOSTOMIA",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "PIEZAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU22",
    "nombre": "BOLSA DE ORINA NIPRO",
    "descripcion": "2000 ML",
    "marca": "NIPRO",
    "fabricante": "NIPRO",
    "unidad": "BOLSA",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU20",
    "nombre": "BOLSA DE ORINA NORMAL",
    "descripcion": "2000 ML NORMAL",
    "marca": "OPTIMED",
    "fabricante": "OPTIMED",
    "unidad": "BOLSA",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU21",
    "nombre": "BOLSA DE ORINA PEDIATRICA",
    "descripcion": "BOLSA DE ORINA PEDIATRICA",
    "marca": "OPTIMED/",
    "fabricante": "OPTIMED",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "POM1",
    "nombre": "BONABEN LOCION",
    "descripcion": "LOCION LIMPIADORA 120 ML",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BONAGEL",
    "descripcion": "HIDROXIDO DE MAGNESIO 200 MG HIDROXIDO DE ALUMINIO 200 MG ANTIACIDO 120 ML",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BONAGEL PLUS",
    "descripcion": "HIDROXIDO DE ALUMINIO 400 MG HIDROXIDO DE MAGNESIO 400 MG SIMETICONA 60 MG",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU23",
    "nombre": "BRANULA Nº 14",
    "descripcion": "Nº 14",
    "marca": "NIPRO",
    "fabricante": "NIPRO",
    "unidad": "ESTUCHE",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU25",
    "nombre": "BRANULA Nº 18",
    "descripcion": "Nº 18",
    "marca": "NIPRO",
    "fabricante": "NIPRO",
    "unidad": "ESTUCHE",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU26",
    "nombre": "BRANULA Nº 20",
    "descripcion": "Nº 20",
    "marca": "NIPRO",
    "fabricante": "NIPRO",
    "unidad": "ESTUCHE",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU27",
    "nombre": "BRANULA Nº 22",
    "descripcion": "Nº 22",
    "marca": "NIPRO",
    "fabricante": "NIPRO",
    "unidad": "ESTUCHE",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU28",
    "nombre": "BRANULA Nº24",
    "descripcion": "Nº 24",
    "marca": "NIPRO",
    "fabricante": "NIPRO",
    "unidad": "ESTUCHE",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDIF",
    "nombre": "BRONCOFLU",
    "descripcion": "AMBROXOL 15 MG 100 ML",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDS7",
    "nombre": "BROXMOL MR",
    "descripcion": "AMBROXOL 15 MG SALBUTAMOL 1MG EXPECTORANTE JARABE",
    "marca": "SAE",
    "fabricante": "SAE",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "BUPIGOBBI 0.5% HIPERBARICA",
    "descripcion": "CLORHIDRATO BUPIVACAINA 5MG/ML",
    "marca": "GOBBI NOVAG",
    "fabricante": "GOBBI NOVAG",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV8",
    "nombre": "BUPIVACAINA 0,5% 10 ML",
    "descripcion": "BUPIVACAINA CLORHIDRATO 5 MG/ML 10 ML ISOBARICA",
    "marca": "INTI PHARMANDINA",
    "fabricante": "INTI PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIPM7",
    "nombre": "BUTAMOL",
    "descripcion": "SALBUTAMOL EN GOTAS",
    "marca": "PRO MEDICAL",
    "fabricante": "PRO MEDICAL",
    "unidad": "GOTERO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV10",
    "nombre": "BUTIL BROMURO HIOSCINA",
    "descripcion": "2 ML",
    "marca": "PHARMANDINA",
    "fabricante": "PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "C TRIX",
    "descripcion": "CEFTRIAXONA 1 GR",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIV",
    "nombre": "C VIMIN + ZINC",
    "descripcion": "VITAMINA C MAS ZINC",
    "marca": "INTI VIMIN",
    "fabricante": "INTI VIMIN",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU31",
    "nombre": "CANULA DE ASPIRACION Nº 12",
    "descripcion": "Nº 12",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "TUBO",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU34",
    "nombre": "CANULA DE ASPIRACION Nº 18",
    "descripcion": "Nº 18",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "TUBO",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INS29-1",
    "nombre": "CANULA DE ASPIRACION Nº 6",
    "descripcion": "CANULA DE ASPIRACION N 6",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU29",
    "nombre": "CANULA DE ASPIRACION Nº 8",
    "descripcion": "Nº 8",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "TUBO",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU32",
    "nombre": "CANULA DE ASPIRACION Nº14",
    "descripcion": "Nº 14",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "TUBO",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU33",
    "nombre": "CANULA DE ASPIRACION Nº16",
    "descripcion": "Nº 16",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "TUBO",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDISLCH1",
    "nombre": "CARBAMAZEPINA LCH",
    "descripcion": "200 MG ANTICONVULSIVO",
    "marca": "LCH",
    "fabricante": "LCH",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIV",
    "nombre": "CARDIO VIMIN",
    "descripcion": "ACIDO FOLICO",
    "marca": "INTI VIMIN",
    "fabricante": "INTI VIMIN",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDISLCH2",
    "nombre": "CARVEDILOL 12,5",
    "descripcion": "12,5 MG SS- BLOQUEANTE VASODILATADOR",
    "marca": "LCH",
    "fabricante": "LCH",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDISLCH6",
    "nombre": "CARVEDILOL 6.25",
    "descripcion": "6,25 MG SS- BLOQUEANTE VASODILATADOR",
    "marca": "LCH",
    "fabricante": "LCH",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDH",
    "nombre": "CEFACRIS 500",
    "descripcion": "60 ML",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CEFALEXINA",
    "descripcion": "500 MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFAZOHAN",
    "descripcion": "CEFAZOLINA 1 GR",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "VIALES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CEFIXIMA",
    "descripcion": "400 MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDT",
    "nombre": "CEFOTAXIM 1",
    "descripcion": "CEFOTAXIMA 1000 MG",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFOTAXIMA",
    "descripcion": "CEFOTAXIMA 1 GR",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV13",
    "nombre": "CEFTADIZIMA",
    "descripcion": "CEFTADIZIMA 1 GR",
    "marca": "NOVO PHARMA",
    "fabricante": "NOVO PHARMA",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI15",
    "nombre": "CEFTRIAX 1000",
    "descripcion": "CEFTRIAXONA 1000 MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7809",
    "nombre": "CEFTRIAXON 1GR",
    "descripcion": "ANTIBIOTICO",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7796",
    "nombre": "CELEPID 20%",
    "descripcion": "FRASCO 500ML",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI",
    "nombre": "CEPILLO CITOLOGICO",
    "descripcion": "CEPILLO",
    "marca": "TAMIVA",
    "fabricante": "TAMIVA",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU172",
    "nombre": "CERA DE HUESO",
    "descripcion": "CERA DE HUESO",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDIIN-B45",
    "nombre": "CERTOFIX DUO PAED S 413",
    "descripcion": "CERTOFIX DUO PAED S 413",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "ESTUCHE",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU7818",
    "nombre": "CERTOFIX DUO S720",
    "descripcion": "CERTOFIX DUO S720",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "PACK",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU7818",
    "nombre": "CERTOFIX TRIO S720",
    "descripcion": "CERTOFIX TRIO S720",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "PACK",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDM",
    "nombre": "CEUMID 500",
    "descripcion": "LEVETIRACETAM 500MG/5ML",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDM",
    "nombre": "CEUMID 500 COMP",
    "descripcion": "LEVETIRACETAM 500 MG",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7830",
    "nombre": "CIPRODEX",
    "descripcion": "COLIRIO 5ML",
    "marca": "SAVAL",
    "fabricante": "SAVAL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CIPROFLOXACINA",
    "descripcion": "500 MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV15",
    "nombre": "CIPROFLOXACINA",
    "descripcion": "CIPROFLOXACINA 200MG",
    "marca": "PHARMANDINA",
    "fabricante": "PHARMANDINA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU123",
    "nombre": "CLAMP UMBILICAL",
    "descripcion": "CLAMP UMBILICAL",
    "marca": "UNILENE",
    "fabricante": "UNILENE",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "AYV14",
    "nombre": "CLINDALCOS",
    "descripcion": "CLINDAMICINA 600MG/50ML",
    "marca": "ALCOS",
    "fabricante": "ALCOS",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU124",
    "nombre": "CLIP",
    "descripcion": "CLIP MEDIANO/LARGO",
    "marca": "ETHICON",
    "fabricante": "ETHICON",
    "unidad": "ESTUCHE",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDIB20",
    "nombre": "CLOFENAC 75",
    "descripcion": "DICLOFENACO SODICO 75 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO RECUBIERTO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB9",
    "nombre": "CLOFENAC 75",
    "descripcion": "DICLOFENACO SODICO 75 MG/3ML ANALGESICO ANTIINFLAMATORIO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB8",
    "nombre": "CLOFENAC RELAX",
    "descripcion": "DICLOFENACO SODICO 75MG/3ML PRIDINOL MESILATO 2,2MG/3ML MIORRELAJANTE ANALGESICO ANTIINFLAMATORIO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7814",
    "nombre": "CLOFENAC RELAX COMPRIMIDO",
    "descripcion": "MIORRELAJANTE ANALGESICO ANTIINFLAMATORIO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7805",
    "nombre": "CLOFENAC RELAX FORTE X CAPSULAS",
    "descripcion": "MIORRRELAJANTE ANALGESICO ANTIINFLAMATORIO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7792",
    "nombre": "CLOFEXAN FORTE",
    "descripcion": "DICLOFENACO 75MG PARACETAMOL 750MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CLOFEXAN RELAX FORTE",
    "descripcion": "DICLOFENACO 75 MG - PRIDINOL 4 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CLOFEXAN50",
    "descripcion": "DICLOFENACO SODICO 50MG PARACETAMOL 500 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDC",
    "nombre": "CLOPIDOGREL",
    "descripcion": "CLOPIDOGREL 75 MG",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "POM23",
    "nombre": "CLORANFENICOL 1%",
    "descripcion": "UNGENTO OFTALMICO 3,5G",
    "marca": "SAVAL",
    "fabricante": "SAVAL",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIS6",
    "nombre": "CLOREX",
    "descripcion": "CLORHEXIDINA ANTISEPTICO BUCOFARINGEO ENJUAGUE BUCAL",
    "marca": "SIGMA",
    "fabricante": "SIGMA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV17",
    "nombre": "CLORFERINAMINA",
    "descripcion": "CLORFERINAMINA 10 MG/1ML",
    "marca": "DISMEDIN",
    "fabricante": "DISMEDIN",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CLORURO DE POTASIO",
    "descripcion": "CLORURO DE POTASIO 10 ML",
    "marca": "PHARMANDINA",
    "fabricante": "PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV19",
    "nombre": "CLORURO DE SODIO",
    "descripcion": "CLORURO DE SODIO 20 ML",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI",
    "nombre": "CLORURO DE SODIO 10",
    "descripcion": "CLORURO DE SODIO 10 ML",
    "marca": "PHARMANDINA",
    "fabricante": "PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CLOTRIM 1%",
    "descripcion": "CLOTRIMOXAZOL 1% 20 MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIV",
    "nombre": "COBA VIMIN 25000",
    "descripcion": "COBAVIMIN",
    "marca": "INTI VIMIN",
    "fabricante": "INTI VIMIN",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CODEINA 10 MG",
    "descripcion": "FRASCO ANTITUSIVO",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV40",
    "nombre": "COFALGINA",
    "descripcion": "METAMIZOL 1 GR",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "COLINA",
    "descripcion": "500 MG",
    "marca": "ARGENTINO",
    "fabricante": "ARGENTINO",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "COLLARIN S M L",
    "descripcion": "COLLAR CERVICAL TALLA S M L",
    "marca": "HEMC",
    "fabricante": "HEMC",
    "unidad": "COLLAR",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDCR",
    "nombre": "COMPAZ",
    "descripcion": "DIAZEPAM 10 MG",
    "marca": "CRISTALIA",
    "fabricante": "CRISTALIA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "COMPLEJO B AMP",
    "descripcion": "COMPLEJO B",
    "marca": "PHARMANDINA",
    "fabricante": "PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIV",
    "nombre": "COMPLEJO B VIMIN",
    "descripcion": "COMPLEJO B",
    "marca": "INTI VIMIN",
    "fabricante": "INTI VIMIN",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIV",
    "nombre": "COMPLEJO B VIMIN COMP",
    "descripcion": "COMPLEJO B",
    "marca": "INTI VIMIN",
    "fabricante": "INTI VIMIN",
    "unidad": "TABLETA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU35",
    "nombre": "CONECTOR CONTRASTE",
    "descripcion": "CHINO",
    "marca": "CHINO",
    "fabricante": "CHINO",
    "unidad": "TUBO",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDM",
    "nombre": "CORENTEL 5",
    "descripcion": "BISOPROLOL FUMARATO 5 MG",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBPH25",
    "nombre": "CORTIMED 8",
    "descripcion": "DEXAMETAZONA FOSFATO 8 MG/2ML ANTIINFLAMATORIU O ANTIALERGICO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTISTAMIN",
    "descripcion": "DEXAMETAZONA 4 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTISTAMIN FORTE",
    "descripcion": "DEXAMETAZONA 8 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTYPIREN",
    "descripcion": "BETAMETAZONA 4 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7785",
    "nombre": "CORTYPIREN GOTAS",
    "descripcion": "CORTICOSTEROIDE",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "GOTERO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "COTRIMOXAZOL",
    "descripcion": "400/80MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDINT13",
    "nombre": "COTRIZOL FORTE",
    "descripcion": "SULFAMETOXAZOL TRIMETROPINA",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "CRONOBECOR",
    "descripcion": "ANTIINFLAMATORIO ANTIALERGICO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB3",
    "nombre": "CRONOCORTEROID",
    "descripcion": "BETAMETASONA DIPROPIONATO10 MG/2ML BETAMETASONA FOSFATO 4 MG/2ML CORTICOSTEROIDE DE ACCION RAPIDA Y PROLONGADA",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "VIAL + JERINGA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "EQUI7781",
    "nombre": "CUBRE CALZADOS",
    "descripcion": "CUBRE CALZADOS ANTIDESLIS",
    "marca": "SALUR",
    "fabricante": "SALUR",
    "unidad": "PAR",
    "tipo": "EQUIPAMIENTO"
  },
  {
    "codigo": "INSU36",
    "nombre": "CUBREZAPATOS QX",
    "descripcion": "CUBRE ZAPATOS QX",
    "marca": "CHINO",
    "fabricante": "CHINO",
    "unidad": "PARES",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "FRASCO5",
    "nombre": "CURADIL 90",
    "descripcion": "SALES DE REHIDRATACION ORAL 90 MEQ/ML",
    "marca": "ALCOS",
    "fabricante": "ALCOS",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIM2",
    "nombre": "DAPAGLICIN 10",
    "descripcion": "DAPAGLIFLOZINA 10 MG ANTIDIABÉTICO",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "COMPRIMIDO RECUBIERTO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "DAPAMET 10/1000",
    "descripcion": "DAPAGLIFOZINA 10 METFORMINA 1000",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DEHIDROLIT 75",
    "descripcion": "SALES DE REHIDRATACION",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "FRASCO3",
    "nombre": "DEHIDROLIT S 75",
    "descripcion": "SALES DE REHIDRATACION ORAL 75MEQ/ML",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI",
    "nombre": "DEMOTIL AG",
    "descripcion": "DEMOTIL AG GOTAS",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "GOTERO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DEMOTIL AMPOLLA",
    "descripcion": "PROPINOXATO",
    "marca": "INTI PHARMANDINA",
    "fabricante": "INTI PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI",
    "nombre": "DEMOTIL GOTAS",
    "descripcion": "DEMOTIL GOTAS",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "GOTERO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDM",
    "nombre": "DEOFLORA",
    "descripcion": "ESPORAS DE BACILLUS CLAUSII 2.000 MILLONES UFC",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "AMPOLLA ORAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "POM7",
    "nombre": "DERMOTRIZINC",
    "descripcion": "OXIDO DE ZINC PASTA",
    "marca": "INTI PHARMANDINA",
    "fabricante": "INTI PHARMANDINA",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV23",
    "nombre": "DEXACOFAZONA 4",
    "descripcion": "DEXAMETAZONA 4 MG",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DEXACOFAZONA 8",
    "descripcion": "DEXAMETAZONA 8 MG",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DEXALIVIUM",
    "descripcion": "DEXKETOPROFENO 50 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI6760",
    "nombre": "DEXAMETASONA OFTALMICA",
    "descripcion": "DEXAMETASONA OFTALMICA 0.1%",
    "marca": "ALCOA",
    "fabricante": "ALCOA",
    "unidad": "CAJA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "DEXAMINO FUERTE",
    "descripcion": "COLAGOGO HEPATOPROTECTO R",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "ESTUCHE",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7823",
    "nombre": "DEXAMINO FUERTE AMPOLLA",
    "descripcion": "COLAGOGO, HEPATOPROTECTO R DESINTOXICANTE",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "DEXAMINO ORAL",
    "descripcion": "L ORNITINA L ASPARTATO 3 GR",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDH",
    "nombre": "DEXMEDETOMIDINA 100MCG",
    "descripcion": "100 MCG",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DEXTROMETORFAN O 10 MG",
    "descripcion": "ANTITUSIVO 100 ML",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DICLOFENACO 1%",
    "descripcion": "DICLOFENACO GEL",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDH",
    "nombre": "DICLOFENACO 50",
    "descripcion": "DICLOFENACO 50 MG",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DICLOFENACO 75",
    "descripcion": "DICLOFENACO 75 MG",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DICLOXACILINA",
    "descripcion": "500 MG ANTIBIOTICO",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "CAPSULAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DICLOXACILINA 250 MG",
    "descripcion": "ANTIBIOTICO 100 ML",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBPH9",
    "nombre": "DIGESTOGAS 300",
    "descripcion": "SIMETICONA 300 MG ANTIFLATULENTO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDISLCH3",
    "nombre": "DIGOXINA LCH",
    "descripcion": "0,25 MG CARDIOLOGICO COMPRIMIDOS",
    "marca": "LCH",
    "fabricante": "LCH",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "0",
    "nombre": "DIOXADOL",
    "descripcion": "DIPIRONA MAGNESICA 1000 MG/4ML ANALGESICO ANTIPIRETICO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB10",
    "nombre": "DIOXADOL",
    "descripcion": "DIPIRONA MAGNESUCA 1000 MG/4ML ANALGESICO ANTIPIRETICO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB11",
    "nombre": "DIOXADOL FORTE",
    "descripcion": "DIPIRONA MAGNESICA 2000 MG/ 5ML ANALGESICO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB26",
    "nombre": "DIOXADOL G",
    "descripcion": "METAMIOL SODICO 500 MG/ML ANTIPIRETICO ANALGESICO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "GOTERO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DIPIN 20",
    "descripcion": "NIMODIPINO 20 MG",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDINT3",
    "nombre": "DIPOSAN 100",
    "descripcion": "DIMETILPOLISILOXA NO 100 MG ANTIFLATULENTO",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "TABLETA MASTTICABLE",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7807",
    "nombre": "DIPROFEN 400MG CAPSULAS BLANDAS",
    "descripcion": "DIPROFEN 400MG CAPSULAS BLANDAS",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7808",
    "nombre": "DIPROFEN 600MG CAPSULAS BLANDAS",
    "descripcion": "DIPROFEN 600MG CAPSULAS BLANDAS",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB20",
    "nombre": "DISCOFIX C",
    "descripcion": "LLAVE DE 3 VIAS CON ALARGADOR",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DISIDOL 60",
    "descripcion": "KETOROLACO 60 MG/2ML",
    "marca": "QUIMFA",
    "fabricante": "QUIMFA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI4",
    "nombre": "DIURENYL",
    "descripcion": "DIURETICO",
    "marca": "SAE",
    "fabricante": "SAE",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDA",
    "nombre": "DOBUTAMINA 12.5 ML",
    "descripcion": "12.5 MG",
    "marca": "ARGENTINO",
    "fabricante": "ARGENTINO",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBPH20",
    "nombre": "DOLOCOFAMIN 25.000 B12 FORTE",
    "descripcion": "DICLOFENACO 75 MG VITAMINA V 12 25.000UG/3ML ANTINEURITICO ANTIINFLAMATORIO ANALGESICO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "ESTUCHE UNIDOSIS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "POM10",
    "nombre": "DOLOCOFAMIN 5%",
    "descripcion": "GEL 2%",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "DOLOFLEXICAM",
    "descripcion": "MELOXICAM 15 MG GLUCOSAMINA 1500 MG",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI9",
    "nombre": "DOLOGRIP INFANTIL",
    "descripcion": "100 ML",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDITC1",
    "nombre": "DOMPER",
    "descripcion": "DOMPERIDONA 10 MG",
    "marca": "TECNOFARMA",
    "fabricante": "TECNOFARMA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "DOMPER DIGEST",
    "descripcion": "DOMPER",
    "marca": "TECNOFARMA",
    "fabricante": "TECNOFARMA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "DORFLEX",
    "descripcion": "PRIDINOL 4MG DICLOFENACO 50 MG",
    "marca": "FORTIER",
    "fabricante": "FORTIER",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "DORFLEX AMP",
    "descripcion": "PRIDINOL2.2 MG DICLOFENACO 75 MG",
    "marca": "FORTIER",
    "fabricante": "FORTIER",
    "unidad": "ESTUCHE",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DYPIRETIC",
    "descripcion": "METAMIZOL SODICO",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDH",
    "nombre": "ECHINACEA COMPLEX",
    "descripcion": "SPRAY",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI6766",
    "nombre": "ELECTRODO ADULTO",
    "descripcion": "ELECTRODO ADULTO",
    "marca": "SALUR",
    "fabricante": "SALUR",
    "unidad": "PIEZAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU126",
    "nombre": "ELECTRODOS PEDIATRICO",
    "descripcion": "ELECTRODOS PEDIATRICO/NEON ATO",
    "marca": "3M",
    "fabricante": "3M",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDF",
    "nombre": "EMPAGLYP 25",
    "descripcion": "EMPAGLIFLOZINA 25 MG",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ENCIFER",
    "descripcion": "ANTIANEMICO",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "ENEMA VIT",
    "descripcion": "SOL FRASCO",
    "marca": "VITA",
    "fabricante": "VITA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI3",
    "nombre": "ENOXPRIM 20",
    "descripcion": "20MG/0,2ML ANTITROMBOTICO",
    "marca": "SAE",
    "fabricante": "SAE",
    "unidad": "JERINGA PRERELLENADA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDT",
    "nombre": "ENTEROCOLIN",
    "descripcion": "200 MG",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU38",
    "nombre": "EQUIPO ARCOMED AG",
    "descripcion": "ARCOMED",
    "marca": "SALUR",
    "fabricante": "SALUR",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "EQUI7815",
    "nombre": "EQUIPO DE VENOCLISIS",
    "descripcion": "INSUMO",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "SACHET",
    "tipo": "EQUIPAMIENTO"
  },
  {
    "codigo": "MEDLQ7",
    "nombre": "ERGO 0.2",
    "descripcion": "ERGOMETRINA 0,2MG OXITOCICO",
    "marca": "LAQFAGAL",
    "fabricante": "LAQFAGAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDLQ6",
    "nombre": "ERGO INYECTABLE",
    "descripcion": "ERGOMETRINA 0,2MG/ML ESTIMULANTE LA CONTRACCION DEL PARTO",
    "marca": "LAQFAGAL",
    "fabricante": "LAQFAGAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "ESPARADRAPO",
    "descripcion": "ESPARADRAPO",
    "marca": "CREMER",
    "fabricante": "CREMER",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7784",
    "nombre": "ESPASMO DIOXADOL GOTAS",
    "descripcion": "ANTIESPASMODICO GOTAS",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "ESPASMO-DIOXADOL PLUS",
    "descripcion": "ANTIESPASMODICO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "ESPASMODIOXADOL PLUS",
    "descripcion": "ANTIHESPASMODIC O",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBPH18",
    "nombre": "ESPASMOLOXADIM",
    "descripcion": "DIPIRONA 2 G/4ML PROPINOXATO 30 MG/1ML ANTIESPAMODICO ANALGESICO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "CAJA X 2 AMP",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBPH16",
    "nombre": "ESPASMOLOXADIM FORTE",
    "descripcion": "DIPIRONA 2,5MG/4ML PRPINOXATO 30 MG/1ML ANTIESPASMODICO ANALGESICO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "CAJA X 2 AMP",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "ESPASMOLOXADIM FORTE COMP",
    "descripcion": "ANTIESPASMODICO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU40",
    "nombre": "ESPATULA CERVICAL",
    "descripcion": "ENDOCERVICAL O DE AYRE",
    "marca": "CHINO",
    "fabricante": "CHINO",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MED",
    "nombre": "ESPECULO DESECHABLE S M L",
    "descripcion": "ESPECULO TALLA S M L",
    "marca": "BLANCO Y NEGRO",
    "fabricante": "BLANCO Y NEGRO",
    "unidad": "ESPECULO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "ESPONJA HEMOSTATICA",
    "descripcion": "10*12 CM",
    "marca": "EQUISPON",
    "fabricante": "EQUISPON",
    "unidad": "ESTUCHE",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI6771",
    "nombre": "ETILEFRINA CLORHIDRATO 10MG",
    "descripcion": "ETILEFRINA CLORHIDRATO 10MG",
    "marca": "ARGENTINO",
    "fabricante": "ARGENTINO",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIM4",
    "nombre": "EUKENE 40",
    "descripcion": "OLMESARTAN MEDOXOMILO 40 MG ANTIHIPERTENSIVO",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "COMPRIMIDO RECUBIERTO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7800",
    "nombre": "EUTIROX 100MCG",
    "descripcion": "LEVOTIROXINA SODICA",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "EUTIROX 50",
    "descripcion": "LEVOTIROXINA 50 MCG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIPM2",
    "nombre": "EVIGAX FORTE",
    "descripcion": "SIMETICONA 250 MG ANTIFLATULENTO",
    "marca": "PROCAPS",
    "fabricante": "PRO MEDICAL",
    "unidad": "CAPSULA BLANDA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIIN-B32",
    "nombre": "EXADROP",
    "descripcion": "EXADROP",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB15",
    "nombre": "EXTENSOFIX 120CM",
    "descripcion": "EXTENSOR",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDLQ3",
    "nombre": "FENITOGAL",
    "descripcion": "FENITOINA 50MG/1ML ANTICONVULSIVAN TE",
    "marca": "LAQFAGAL",
    "fabricante": "LAQFAGAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "0",
    "nombre": "FENITOINA 100",
    "descripcion": "FENITOINA SODICA 100 MG",
    "marca": "LCH",
    "fabricante": "LCH",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDA",
    "nombre": "FENITOINA 100AMP",
    "descripcion": "100 MG",
    "marca": "ARGENTINO",
    "fabricante": "ARGENTINO",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "FENTANILO 0.5",
    "descripcion": "FENTANILO",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "FILINAR G",
    "descripcion": "5 MG",
    "marca": "EUROFARMA",
    "fabricante": "EUROFARMA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU41",
    "nombre": "FILTRO ANTIBACTERIANO",
    "descripcion": "ANTIBACTERIANO",
    "marca": "CHINO",
    "fabricante": "CHINO",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDBP",
    "nombre": "FIXIM 100",
    "descripcion": "CEFIXIMA 100 MG/5 ML 50 ML",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "FIXIM 400",
    "descripcion": "CEFIXIMA",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "FIXIM FORTE",
    "descripcion": "CEFIXIMA 200MG/5ML 50 ML",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI4",
    "nombre": "FLAMADIN B12",
    "descripcion": "DICLOFENACO 75MG CIANOCOBALAMINA 10000 UG ANALGESICO ANTIINFLAMATORIO ANTINEURITICO",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI5",
    "nombre": "FLAMADIN B12 FORTE",
    "descripcion": "DICLOFENACO 75MG CIANOCOBALAMINA 25000 UG ANALGESICO ANTIINFLAMATORIO ANTINEURITICO",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7",
    "nombre": "FLAMADIN PLUS",
    "descripcion": "DICLOFENACO 50 MG PARACETAMOL 500 MG ANALGESICO ANTIINFLAMATORIO",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "CAPSULA BLANDA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV",
    "nombre": "FLAMAX",
    "descripcion": "MELOXICAM 15 MG/1.5ML",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "FLAVO CKR",
    "descripcion": "FLAVO CKR",
    "marca": "VITA",
    "fabricante": "VITA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBPH15",
    "nombre": "FLEXICAM B12 FORTE",
    "descripcion": "MELOXICAM 15 MG VITAMINA B12 25,00UG/3ML ANTIINFLAMATORIO ANALGESICO ANTINEURITICO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "ESTUCHE UNIDOSIS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "FLEXICAM RELAX SL",
    "descripcion": "MELOXICAM 15 PRIDINOL 4 MG",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "COMPRIMIDO SUBLINGUAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI6689",
    "nombre": "FLOGIATRIN AMPOLLA",
    "descripcion": "AMPOLLA B12 NF",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI6690",
    "nombre": "FLOGIATRIN POMADA",
    "descripcion": "POMADA 100GR",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7812",
    "nombre": "FLOGOCOX 90",
    "descripcion": "ANTIINFLAMATORIO NO ESTEROIDEO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "FLORESTOR",
    "descripcion": "FLORESTOR",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV1",
    "nombre": "FLUICETIL",
    "descripcion": "ACETILCISTEINA 300 MG/3ML",
    "marca": "SAN FERNANDO",
    "fabricante": "SAN FERNANDO",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "FLUIDIMED PRO",
    "descripcion": "ACETILCISTEINA 600 PROPOLEO DROSERA GRINDELIA MENTA",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDISLCH7",
    "nombre": "FLUOXETINA LCH",
    "descripcion": "20 MG ANTIDEPRESIVO",
    "marca": "LCH",
    "fabricante": "LCH",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "FLUOXOL",
    "descripcion": "FLUCONAZOL 200 MG",
    "marca": "ALCOS",
    "fabricante": "ALCOS",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "FORACORT",
    "descripcion": "4 MG",
    "marca": "SAE",
    "fabricante": "SAE",
    "unidad": "TABLETA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "FORFIG 300",
    "descripcion": "300 MG",
    "marca": "EUROFARMA",
    "fabricante": "EUROFARMA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "FORTICAM 3B",
    "descripcion": "MELOXICAM 15 MG VITAMINA B1B6B12",
    "marca": "FORTIER",
    "fabricante": "FORTIER",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDQ4",
    "nombre": "FORTINIL 1000",
    "descripcion": "CITICOLINA 1000MG/5ML VASODILATADOR CEREBRAL",
    "marca": "QUIMFA",
    "fabricante": "QUIMFA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU43",
    "nombre": "FRASCO DE HECES",
    "descripcion": "EXAMEN DE HECES",
    "marca": "JOSA",
    "fabricante": "JOSA",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU42",
    "nombre": "FRASCO DE ORINA",
    "descripcion": "COLECTOR DE ORINA",
    "marca": "BQS",
    "fabricante": "BQS",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDI7783",
    "nombre": "FUROSEMIDA 20MG/2ML",
    "descripcion": "FUROSEMIDA 20MG/2ML",
    "marca": "ABD/GOBBI",
    "fabricante": "ABD/GOBBI",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI11",
    "nombre": "GABENOL 1",
    "descripcion": "ANALGESICO OPIOIDE 1GR",
    "marca": "SAE",
    "fabricante": "SAE",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI12",
    "nombre": "GABENOL 2",
    "descripcion": "BUTORFANOL 2 MG ANALGESICO OPIOIDE",
    "marca": "SAE",
    "fabricante": "SAE",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDLQ1",
    "nombre": "GALAMINOFIL 250",
    "descripcion": "AMINOFILINA 250 MG BRONCODILATADO R",
    "marca": "LAQFAGAL",
    "fabricante": "LAQFAGAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "GANEUM 150",
    "descripcion": "NEUROMODULADO R - ANTIEPILECTICO",
    "marca": "PREGABALINA 150MG",
    "fabricante": "FARMEDICAL",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "GANEUM 75",
    "descripcion": "PREGABALINA 75 MG",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "OTRO7777",
    "nombre": "GASA 100 YARDAS",
    "descripcion": "GASA QUIRURGICAS",
    "marca": "PREMIER",
    "fabricante": "PREMIER",
    "unidad": "BOLSA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "GASSTOP",
    "descripcion": "SIMETICONA 150 MG",
    "marca": "FORTIER",
    "fabricante": "FORTIER",
    "unidad": "COMPRIMIDO MASTICABLE",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7822",
    "nombre": "GELBRONQUIAL",
    "descripcion": "SALBUTAMOL CLORHIDRATO DE AMBROXOL",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "GENTACOFAR",
    "descripcion": "GENTAMICINA 80 MG/ML",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIPM8",
    "nombre": "GENTAMICINA 0.3%",
    "descripcion": "GENTAMICINA SOLUCION OFTALMICA",
    "marca": "PRO MEDICAL",
    "fabricante": "PRO MEDICAL",
    "unidad": "GOTERO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI6762",
    "nombre": "GENTAMICINA 80ML",
    "descripcion": "AMPOLLAS DE 80ML",
    "marca": "MUNDO PHARMA",
    "fabricante": "MUNDO PHARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "GLICENEX 500",
    "descripcion": "METFORMINA 500 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "GLICENEX DUO 500/5",
    "descripcion": "METFORMINA 500 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "GLUCOCINTAS",
    "descripcion": "TIRAS REACTIVAS",
    "marca": "PREMIER",
    "fabricante": "PREMIER",
    "unidad": "TIRAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "GLUCONATO DE CALCIO 10%",
    "descripcion": "SUPLEMENTO DE CALCIO",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIIN-B12",
    "nombre": "GLUCOSA 20% 500 ML",
    "descripcion": "GLUCOSA 20% 500 ML",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV30",
    "nombre": "GLUCOSA 33%",
    "descripcion": "GLUCOSA 33%",
    "marca": "ALFA",
    "fabricante": "ALFA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "SOL",
    "nombre": "GLUCOSA 5% 500 ML",
    "descripcion": "SOL GLUCOSA 5%",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI6696",
    "nombre": "GLUCOSA 50% 500ML",
    "descripcion": "GLUCOSA 50% 500ML",
    "marca": "INTI- BRAUN",
    "fabricante": "BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV4",
    "nombre": "GLUMIKIN",
    "descripcion": "AMIKACINA 500MG/2ML",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7811",
    "nombre": "GOLPEX SPRAY",
    "descripcion": "ANALGESICO ANTIINFLAMATORIO",
    "marca": "SAE",
    "fabricante": "SAE",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU45",
    "nombre": "GORROS QUIRURGICOS",
    "descripcion": "GORROS QUIRURGICOS",
    "marca": "CHINO",
    "fabricante": "CHINO",
    "unidad": "UNIDADES",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDSF",
    "nombre": "GRAVOL",
    "descripcion": "DIMENHIDRINATO 50 MG ANTIEMETICO ANTIVERTIGINOSO",
    "marca": "SAN FERNANDO",
    "fabricante": "SAN FERNANDO",
    "unidad": "TABLETA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIM5",
    "nombre": "GRIPETIL",
    "descripcion": "JUGO SECADO DE ECHINACEA PURPUREA 180 MG GRIPE Y RESFRIO",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "COMPRIMIDO RECUBIERTO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU47",
    "nombre": "GUANTES DE LATEX M",
    "descripcion": "TALLA M",
    "marca": "PREMIER/SENCICA RE",
    "fabricante": "PREMIER/SENCIC ARE",
    "unidad": "PARES",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU46",
    "nombre": "GUANTES DE LATEX S",
    "descripcion": "TALLA S",
    "marca": "PREMIER/SENCICA RE",
    "fabricante": "PREMIER/SENCIC ARE",
    "unidad": "PARES",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU49",
    "nombre": "GUANTES ESTERILES Nº 6 1/2",
    "descripcion": "Nº6 1/2",
    "marca": "TES/PREMIER",
    "fabricante": "TES/PREMIER",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDI",
    "nombre": "GUANTES ESTERILES Nº 6 G",
    "descripcion": "GUANTE ESTERIL",
    "marca": "PREMIER",
    "fabricante": "PREMIER",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU50",
    "nombre": "GUANTES ESTERILES Nº 7",
    "descripcion": "Nº 7",
    "marca": "TES/PREMIER",
    "fabricante": "TES/PREMIER",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU51",
    "nombre": "GUANTES ESTERILES Nº 7 1/2",
    "descripcion": "Nº 7 1/2",
    "marca": "TES/PREMIER",
    "fabricante": "TES/PREMIER",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU52",
    "nombre": "GUANTES ESTERILES Nº8",
    "descripcion": "Nº 8",
    "marca": "TES/PREMIER",
    "fabricante": "TES/PREMIER",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MED",
    "nombre": "HEMOVAC Nº 14",
    "descripcion": "SUCCION",
    "marca": "POLYMED",
    "fabricante": "POLYMED",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "HEMOVAC Nº 16",
    "descripcion": "SUCCION",
    "marca": "POLYMED",
    "fabricante": "POLYMED",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "HEPARINA SODICA",
    "descripcion": "5000 UI/ML",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "FRASCO1",
    "nombre": "HIDRATA ABD 75",
    "descripcion": "SALES DE REHIDRATACION ORAL 75MEQ/ML ORAL",
    "marca": "LAB ABD",
    "fabricante": "LAB ABD",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "HIDROCORTIZONA 100",
    "descripcion": "HIDROCORTIZONA 100 MG",
    "marca": "DISMEDIN",
    "fabricante": "DISMEDIN",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "HIDROCORTIZONA 250",
    "descripcion": "HIDROCORTIZONA 250 MG",
    "marca": "DISMEDIN",
    "fabricante": "DISMEDIN",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "HIDROCORTIZONA 500",
    "descripcion": "HIDROCORTIZONA 500 MG",
    "marca": "DISMEDIN",
    "fabricante": "DISMEDIN",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU150",
    "nombre": "HILO CAT GUT CROMADO 0",
    "descripcion": "AGUJA 36 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU151",
    "nombre": "HILO CAT GUT CROMADO 1",
    "descripcion": "AGUJA 40 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU154",
    "nombre": "HILO CAT GUT CROMADO 2",
    "descripcion": "AGUJA 28 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU",
    "nombre": "HILO CAT GUT CROMADO 2/0",
    "descripcion": "AGUJA 40 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU155",
    "nombre": "HILO CAT GUT CROMADO 3/0",
    "descripcion": "AGUJA 25 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU156",
    "nombre": "HILO CAT GUT CROMADO 4/0",
    "descripcion": "AGUJA 15 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU157",
    "nombre": "HILO CAT GUT CROMADO 5/0",
    "descripcion": "AGUJA 15 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU145",
    "nombre": "HILO CAT GUT SIMPLE 0",
    "descripcion": "AGUJA 40 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU146",
    "nombre": "HILO CAT GUT SIMPLE 1",
    "descripcion": "AGUJA 40 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU147",
    "nombre": "HILO CAT GUT SIMPLE 2/0",
    "descripcion": "AGUJA 25 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU148",
    "nombre": "HILO CAT GUT SIMPLE 3/0",
    "descripcion": "AGUJA 25 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU149",
    "nombre": "HILO CAT GUT SIMPLE 4/0",
    "descripcion": "AGUJA 20 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDI",
    "nombre": "HILO CAT GUT SIMPLE 5/0",
    "descripcion": "HILO",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU141",
    "nombre": "HILO NYLON 3/0",
    "descripcion": "AGUJA 20 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU142",
    "nombre": "HILO NYLON 4/0",
    "descripcion": "AGUJA 20 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU144",
    "nombre": "HILO NYLON 6/0",
    "descripcion": "AGUJA 15 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU138",
    "nombre": "HILO NYLON Nº 1",
    "descripcion": "AGUJA 35 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU140",
    "nombre": "HILO NYLON Nº 2/0",
    "descripcion": "AGUJA 24 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU143",
    "nombre": "HILO NYLON Nº 5/0",
    "descripcion": "AGUJA 19 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDI7806",
    "nombre": "HILO POLIPROPILENO 3-0 C/AGUJA MR25 X 75CM",
    "descripcion": "HILO POLIPROPILENO 3-0 C/AGUJA MR25 X 75CM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU",
    "nombre": "HILO POLIPROPILENO 4",
    "descripcion": "AGUJA 20 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU169",
    "nombre": "HILO POLIPROPILENO 5",
    "descripcion": "AGUJA 20 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU159",
    "nombre": "HILO SEDA 1",
    "descripcion": "AGUJA Nº",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "EQUI7803",
    "nombre": "HILO SEDA 2/0",
    "descripcion": "HILO SEDA 2/0",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "SOBRES",
    "tipo": "EQUIPAMIENTO"
  },
  {
    "codigo": "INSU161",
    "nombre": "HILO SEDA 3/0",
    "descripcion": "AGUJA 20 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU162",
    "nombre": "HILO SEDA 3/0",
    "descripcion": "AGUJA 26 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU163",
    "nombre": "HILO SEDA 4/0",
    "descripcion": "AGUJA 17 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU164",
    "nombre": "HILO SEDA 5/0",
    "descripcion": "AGUJA 15 MM",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU165",
    "nombre": "HILO SEDA NEGRA 0",
    "descripcion": "SIN AGUJA",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "EQUI7769",
    "nombre": "HILO SEDA NEGRA 0 CARRETA DE 100 YARDAS",
    "descripcion": "HILO SEDA NEGRA 0 CARRETA DE 100 YARDAS",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "CARRETA 100 YADS",
    "tipo": "EQUIPAMIENTO"
  },
  {
    "codigo": "INSU170",
    "nombre": "HILO SEDA NEGRA 1",
    "descripcion": "CARRETA 100 YDAS",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "CARRETE",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU171",
    "nombre": "HILO SEDA NEGRA 2",
    "descripcion": "CARRETA 50 YARDAS",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "CARRETA",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL N 0",
    "descripcion": "HILO",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "EQUI7770",
    "nombre": "HILO VICRYL N 0 CON AGUJA 40",
    "descripcion": "HILO AGUJA 40",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "EQUIPAMIENTO"
  },
  {
    "codigo": "MEDI6765",
    "nombre": "HILO VICRYL Nª 1 AGUJA 40",
    "descripcion": "HILO DE SUTURA Nª1",
    "marca": "PROBIN",
    "fabricante": "PROBIN",
    "unidad": "PIEZAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 1",
    "descripcion": "HILO",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 2/0",
    "descripcion": "HILO",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 3/0",
    "descripcion": "HILO",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 4/0",
    "descripcion": "HILO",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 5/0",
    "descripcion": "HILO",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 6/0",
    "descripcion": "HILO",
    "marca": "SUTUMED/BYOLINE/ SUTURES VITALIS",
    "fabricante": "SUTUMED/BYOLIN E/SUTURES VITALIS",
    "unidad": "PIEZAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "HIPOPRES 20",
    "descripcion": "LISINOPRIL 20 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "TABLETA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDA3",
    "nombre": "HUESOBONE",
    "descripcion": "MELOXICAM 15 MG ANTIINFLAMATORIO",
    "marca": "ALFA",
    "fabricante": "ALFA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "IBL 1500",
    "descripcion": "AMOXICILINA 1000 SULBACTAM 500 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDSF",
    "nombre": "IBUFLAMAR P",
    "descripcion": "COMP",
    "marca": "SAN FERNANDO",
    "fabricante": "SAN FERNANDO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7828",
    "nombre": "IBUFORT DUO",
    "descripcion": "IBUFORT DUO",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDH",
    "nombre": "IBUMIGRAM",
    "descripcion": "COMPRIMIDO",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDC",
    "nombre": "IBUPROFENO 100",
    "descripcion": "IBUPROFENO 100 MG",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDC",
    "nombre": "IBUPROFENO 200",
    "descripcion": "IBUPROFENO 200 MG",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "IBUPROFENO 400 COMP",
    "descripcion": "IBUPROFENO",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "IBUPROFENO 600 COMP",
    "descripcion": "COMPRIMIDO 600 MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIT3",
    "nombre": "IBUPRONAL 400 MG",
    "descripcion": "IBUPROFENO 400 MG ANTIPIRETICO ANALGESICO ANTIINFLAMATORIO",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "CAPSULAS BLANDAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI14",
    "nombre": "IFOTAXIMA 1000",
    "descripcion": "CEFOTAXIMA 1000 MG ANTIBIOTICO",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7774",
    "nombre": "IMIPENEM CILASTATINA",
    "descripcion": "500 MG",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBPH8",
    "nombre": "INHIBID",
    "descripcion": "PANTOPRAZOL 40 MG ANTIULCEROSO INHIBIDOR DE LA BOMBA DE PROTONES",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI",
    "nombre": "INTIBROXOL ADULTO",
    "descripcion": "AMBROXOL 30 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "INTIBROXOL AMP",
    "descripcion": "AMBROXOL 15 MG/2ML",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI",
    "nombre": "INTIBROXOL INFANTIL",
    "descripcion": "AMBROXOL 15 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB21",
    "nombre": "INTRAFIX PRIMELINE AIR FS",
    "descripcion": "EQUIPO DE INFUSION",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIIN-B34",
    "nombre": "INTROCAN Nº 18",
    "descripcion": "INTROCAN Nº 18",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIIN-B35",
    "nombre": "INTROCAN Nº 20",
    "descripcion": "INTROCAN Nº 20",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU98",
    "nombre": "INTUBATING STYLET",
    "descripcion": "ESTILETE DE INTUBACION Nº 12 FR",
    "marca": "WELLEAD",
    "fabricante": "WELLEAD",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDFS2",
    "nombre": "IPSON",
    "descripcion": "IBUPROFENO 100MG/5 ML 120 ML SUSPENSION",
    "marca": "SAVAL",
    "fabricante": "SAVAL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDH",
    "nombre": "IVERMECTINA",
    "descripcion": "6 MG",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU53",
    "nombre": "JERINGA 1 ML",
    "descripcion": "JERINGA DE INSULINA",
    "marca": "PREMIER/SANJET MEDICAL",
    "fabricante": "PREMIER/SANJET MEDICAL",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "EQUI7826",
    "nombre": "JERINGA 10 ML",
    "descripcion": "10 ML",
    "marca": "PREMIER/SANJET MEDICAL",
    "fabricante": "PREMIER/SANJET MEDICAL",
    "unidad": "SACHET",
    "tipo": "EQUIPAMIENTO"
  },
  {
    "codigo": "INSU57",
    "nombre": "JERINGA 20 ML",
    "descripcion": "20 ML",
    "marca": "PREMIER/SANJET MEDICAL",
    "fabricante": "PREMIER/SANJET MEDICAL",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU54",
    "nombre": "JERINGA 3ML",
    "descripcion": "3 ML",
    "marca": "PREMIER/SANJET MEDICAL",
    "fabricante": "PREMIER/SANJET MEDICAL",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU55",
    "nombre": "JERINGA 5 ML",
    "descripcion": "5 ML",
    "marca": "PREMIER/SANJET MEDICAL",
    "fabricante": "PREMIER/SANJET MEDICAL",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU58",
    "nombre": "JERINGA 50 ML",
    "descripcion": "50 ML",
    "marca": "PREMIER/SANJET MEDICAL",
    "fabricante": "PREMIER/SANJET MEDICAL",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDICR3",
    "nombre": "KETAMIN",
    "descripcion": "CLORHIDRATO DE ESCETAMINA 50 MG (ML ANESTESIA",
    "marca": "CRISTALIA",
    "fabricante": "CRISTALIA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "KETOFLEX DUO",
    "descripcion": "DEXKETOPROFENO 25MG PARACETAMOL 500MG",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "KETOPROFENO 100",
    "descripcion": "KETOPROFENO 100 MG",
    "marca": "PHARMANDINA",
    "fabricante": "PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDFS3",
    "nombre": "KEVAL",
    "descripcion": "ELETRIPTAN 40 MG 40MG COMPRIMIDO",
    "marca": "SAVAL",
    "fabricante": "SAVAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "KIN GINGIVAL COMPLEX",
    "descripcion": "ASEO BUCAL",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "CAJA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "L DEXAMINO",
    "descripcion": "L ORNITINA L ASPARTATO 5 G /10 ML",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "LABEBLOCK",
    "descripcion": "BETABLOQUEANTE LABETALOL",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7778",
    "nombre": "LAGRIMAS ARTIFICIALES",
    "descripcion": "GOTAS OFTALMICAS",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI6691",
    "nombre": "LANZOPRAL AMPOLLA",
    "descripcion": "AMPOLLA 30MG",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU116",
    "nombre": "LAPIZ DE ELECTROBISTURI",
    "descripcion": "ELECTROBISTURI",
    "marca": "COVIDIEN",
    "fabricante": "COVIDIEN",
    "unidad": "ESTUCHE",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDIN",
    "nombre": "LAXUAVE",
    "descripcion": "LAXUAVE ADULTO",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDTC3",
    "nombre": "LERTUS GEL",
    "descripcion": "DICLOFENACO 60 GR GEL",
    "marca": "TECNOFARMA",
    "fabricante": "TECNOFARMA",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "LEUKOMED T",
    "descripcion": "10*25CM",
    "marca": "SALUR",
    "fabricante": "SALUR",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV",
    "nombre": "LEVOALCOAS IV",
    "descripcion": "LEVOFLOXOACINA 500 MG",
    "marca": "ALCOS",
    "fabricante": "ALCOS",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIC3",
    "nombre": "LEVOFLOXACINA 750",
    "descripcion": "LEVOFLOXACINA 750 MG ANTIBIOTICO QUINOLONICO BACTERICIDA DE AMPLIO ASPECTRO",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "COMRIMIDO RECUBIERTO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIPM4",
    "nombre": "LIBBERA",
    "descripcion": "LEVOCETIRIZINA 2,5 MG/5ML ANTIHISTAMINICO JARABE 60 ML",
    "marca": "HERSIL",
    "fabricante": "PRO MEDICAL",
    "unidad": "JARABE",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV136",
    "nombre": "LIDOCAINA 1%",
    "descripcion": "LIDOCAINA 1%",
    "marca": "DISMEDIN",
    "fabricante": "DISMEDIN",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV",
    "nombre": "LIDOCAINA 2% 10",
    "descripcion": "LIDOCAINA 10 ML",
    "marca": "PHARMANDINA",
    "fabricante": "PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "LIDOCAINA 2% 20 ML",
    "descripcion": "LIDOCAINA 2% 20 ML",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV",
    "nombre": "LIDOCAINA 2% 5ML",
    "descripcion": "LIDOCAINA 5 ML",
    "marca": "PHARMANDINA",
    "fabricante": "PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "LIDRAMINA",
    "descripcion": "DIFENHIDRAMINA CLORHIDRATO 20 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIIN-B27",
    "nombre": "LINOVERA",
    "descripcion": "LINOVERA",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7795",
    "nombre": "LIPOFUNDIN 10% FCO 500ML",
    "descripcion": "LIPOFUNDIN 10% FCO 500M",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU59",
    "nombre": "LLAVE DE 3 VIAS",
    "descripcion": "NORMAL",
    "marca": "CHINO",
    "fabricante": "CHINO",
    "unidad": "ESTUCHE",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU61",
    "nombre": "LLAVE DE 3 VIAS CON ALARGADOR 50 CM",
    "descripcion": "CON ALARGADOR 50 CM",
    "marca": "CHINO",
    "fabricante": "CHINO",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDIC2",
    "nombre": "LOSARTAN",
    "descripcion": "LOSARTAN 50 MG ANTIHIPERTENSIVO",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "COMPRIMIDO RECUBIERTO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIPM3",
    "nombre": "MAGAL D",
    "descripcion": "MAGALDRATO SIMETICONA SUSPENSION 200ML",
    "marca": "HERSIL",
    "fabricante": "PRO MEDICAL",
    "unidad": "JARABE",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7782",
    "nombre": "MAGNESIO VIMIN",
    "descripcion": "VITAMINA",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIIN-B14",
    "nombre": "MANITOL 500 ML",
    "descripcion": "SOLUCION MANITOL",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "MAXIBIOTIC 1000",
    "descripcion": "CEFAZOLINA 1000 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBPH26",
    "nombre": "MAXIBONAGEL",
    "descripcion": "MALGRATO MAS SIMETICONA SUSPENCION 200 ML SABOR CHERRY ANTIACIDO ANTIFLATULENTO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MEBIDOX 400",
    "descripcion": "IBUPROFENO 400",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MEBIDOX 600",
    "descripcion": "IBUPROFENO 600",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI6692",
    "nombre": "MEGATADINA",
    "descripcion": "COMPRIMIDOS 10MG",
    "marca": "COMPRIMIDOS 10MG",
    "fabricante": "MEGALABS",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "MELOXICAM",
    "descripcion": "15 MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7827",
    "nombre": "MELOXICAM 15MG",
    "descripcion": "15MG",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDA",
    "nombre": "MEROPENEM 1 GR",
    "descripcion": "ANTIBIOTICO",
    "marca": "LIBRA",
    "fabricante": "LIBRA",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDLQ4",
    "nombre": "METIL PREDGAL",
    "descripcion": "METILPREDNISOLO NA 100 MG CORTICOSTEROIDE",
    "marca": "LAQFAGAL",
    "fabricante": "LAQFAGAL",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "METOCLOPRAMIDA",
    "descripcion": "METOCLOPRAMIDA 10 MG",
    "marca": "INTI PHARMANDINA",
    "fabricante": "INTI PHARMANDINA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI6761",
    "nombre": "METOCLOPRAMIDA 10MG AMPOLLA",
    "descripcion": "AMPOLLA DE 10MG",
    "marca": "PHARMANDINA",
    "fabricante": "PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI6763",
    "nombre": "METOCLOPRAMIDA 10MG MUNDO PHARMA",
    "descripcion": "AMPOLLA DE 10MG",
    "marca": "MUNDO PHARMA",
    "fabricante": "MUNDO PHARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIPM1",
    "nombre": "METROCAPS",
    "descripcion": "METRONIDAZOL 500 MG ANTIBIOTICO",
    "marca": "PRO CAPS",
    "fabricante": "PRO MEDICAL",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV42",
    "nombre": "METROGYN",
    "descripcion": "METRONIDAZOL",
    "marca": "ALCOS",
    "fabricante": "ALCOS",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIIN-B18",
    "nombre": "METRONAC",
    "descripcion": "METRONIDAZOL 1,5 GR",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU62",
    "nombre": "MICROGOTERO 150 ML",
    "descripcion": "150 ML",
    "marca": "CHINO",
    "fabricante": "CHINO",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU130",
    "nombre": "MICROPORE 3M 2.5",
    "descripcion": "MICROPORE",
    "marca": "3M/",
    "fabricante": "3M/",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "EQUI7772",
    "nombre": "MICROPORE 3M 5CMX9,1M",
    "descripcion": "MICROPORE",
    "marca": "3M",
    "fabricante": "3M",
    "unidad": "PIES",
    "tipo": "EQUIPAMIENTO"
  },
  {
    "codigo": "MED",
    "nombre": "MIDAZOLAM 15 MG",
    "descripcion": "MIDAZOLAM",
    "marca": "NOVO PHARMA",
    "fabricante": "NOVO PHARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "MORFINA 10 MG",
    "descripcion": "AMPOLLA",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIT2",
    "nombre": "MOXILIN 1G",
    "descripcion": "AMOXICILINA 1 GR ANTIBACTERIANO",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "COMPRIMIDO RECUBIERTO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIT6",
    "nombre": "MOXILIN 500 MG",
    "descripcion": "AMOXICILINA 500 MG/5ML ANTIBACTERIANO SUSPENSION ORAL SABOR FRESA",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDINT15",
    "nombre": "MUXATIL",
    "descripcion": "ACETILCISTEINA",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MUXATIL AMP",
    "descripcion": "ACETILCISTEINA 300 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDFS4",
    "nombre": "MUXOL",
    "descripcion": "AMBROXOL 15MG/5ML JARABE",
    "marca": "SAVAL",
    "fabricante": "SAVAL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN6",
    "nombre": "MYCOTIX 200",
    "descripcion": "FLUCONAZOL 200 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDA",
    "nombre": "NALOXONA",
    "descripcion": "0.4 MG",
    "marca": "ARGENTINO",
    "fabricante": "ARGENTINO",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDM",
    "nombre": "NASOXY SPRAY",
    "descripcion": "CLORURO DE SODIO 0.9% NASAL",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "SPRAY",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7816",
    "nombre": "NEOSTIGMINA",
    "descripcion": "AMPOLLA",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "NEOTREX",
    "descripcion": "ACIDO TRANEXAMICO 500MG",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDFS11",
    "nombre": "NERBEDOL B 12 10000",
    "descripcion": "DICLOFENACO 75 + VIT B12 AMPOLLA",
    "marca": "SAVAL",
    "fabricante": "SAVAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "NEUROTRAT FORTE 10000",
    "descripcion": "NEUROTRAT",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDFS9",
    "nombre": "NEUROVAL CD 10 MG",
    "descripcion": "CLOTIAZEPAM 10 MG COMPRIMIDO",
    "marca": "SAVAL",
    "fabricante": "SAVAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "NISTATINA",
    "descripcion": "500.000 UI ANTIMICOTICO",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDS8",
    "nombre": "NODOL 400",
    "descripcion": "IBUPROFENO 400MG ANTIINFLAMATORIO ANALGESICO",
    "marca": "SAE",
    "fabricante": "SAE",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIS8",
    "nombre": "NOOPIRAM",
    "descripcion": "NOOTROPICO PIRACETAM 1200MG",
    "marca": "SIGMA",
    "fabricante": "SIGMA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIS4",
    "nombre": "NOOPIRAM",
    "descripcion": "PIRACETAM 1GR/5ML NOOTROPICO",
    "marca": "SIGMA",
    "fabricante": "SIGMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NORADRENALINA",
    "descripcion": "NORADRENALINA 4MG/ML 4ML ESTIMULANTE CARDIACO",
    "marca": "NOVO PHARMA",
    "fabricante": "NOVO PHARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBPH14",
    "nombre": "NOVADOL",
    "descripcion": "DICLOFENACO 50 MG PARACETAMOL 500 MG ANTIINFLAMATORIO ANALGESICO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBPH13",
    "nombre": "NOVADOL 75",
    "descripcion": "DICLOFENACO 75 MG + PARACETAMOL 500 MG ANTIINFLAMATORIO ANALGESICO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIBPH12",
    "nombre": "NOVADOL FORTE",
    "descripcion": "DICLOFENACO 75 MG + PARACETAMOL DC 750 MG ANTIINFLAMATORIO ANALGESICO ANTIPIRETICO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "COMPRIMIDO RECUBIERTO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "POM11",
    "nombre": "NOVADOL GEL",
    "descripcion": "GEL 2%",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDN2",
    "nombre": "NOVO PENCIL 12.6.6",
    "descripcion": "ANTIBIOTICO",
    "marca": "NOVO PHARMA",
    "fabricante": "NOVO PHARMA",
    "unidad": "VIAL+AMPOLLA +JERINGA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NOVONOX 40",
    "descripcion": "ANTITROMBOTICO ENOXAPARINA SODICA 40 MG/0.4ML",
    "marca": "NOVO PHARMA",
    "fabricante": "NOVO PHARMA",
    "unidad": "JERINGA PRELLENADA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NOVONOX 60",
    "descripcion": "ENOXAPARINA SODICA 60MG/0.6ML ANTITROMBOTICO",
    "marca": "NOVO PHARMA",
    "fabricante": "NOVO PHARMA",
    "unidad": "JERINGA PRELLENADA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NOVOPENCIL FORTE 12.6.6",
    "descripcion": "PENICILINA G BENZATINICA PROCAINICA SODICA",
    "marca": "NOVO PHARMA",
    "fabricante": "NOVO PHARMA",
    "unidad": "KIT",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "OMEGASTRIN 20",
    "descripcion": "OMEPRAZOL 20 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "OMEGASTRIN 40",
    "descripcion": "OMEGASTROL",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7828",
    "nombre": "OMEPRAZOL 20MG",
    "descripcion": "OMEPRAZOL 20MG",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV",
    "nombre": "OMEPRAZOL 40 MG",
    "descripcion": "OMERPAZOL 40 MG",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDNV",
    "nombre": "ONDANSETRON",
    "descripcion": "ONDANSETRON 8 MG /4ML",
    "marca": "NOVO PHARMA",
    "fabricante": "NOVO PHARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDQ3",
    "nombre": "OSELTA 75",
    "descripcion": "OSELTAMIVIR 75 MG ANTIVIRAL",
    "marca": "QUIMFA",
    "fabricante": "QUIMFA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "OXAR D",
    "descripcion": "ANTIHIPERTENSIVO",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDLG2",
    "nombre": "OXITOCINA",
    "descripcion": "OXITOCINA 10UI/ML ESTIMULANTE UTERINA E INDUCCION AL PARTO",
    "marca": "LAQFAGAL",
    "fabricante": "LAQFAGAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "PACO",
    "descripcion": "COMPRIMI",
    "marca": "EUROFARMA",
    "fabricante": "EUROFARMA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "PAPEL DE ELECTROCARDIOGR AMA",
    "descripcion": "PAPEL",
    "marca": "SALUR",
    "fabricante": "SALUR",
    "unidad": "ROLLO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "PARACETAMOL 100 MG",
    "descripcion": "GOTAS",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "GOTERO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDH",
    "nombre": "PARACETAMOL 125 MG",
    "descripcion": "PARACETAMOL 125 MG",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "PARACETAMOL 1GR",
    "descripcion": "PARACETAMOL 1G",
    "marca": "INTI- BRAUN DISMEDIN",
    "fabricante": "INTI- BRAUN DISMEDIN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "PAUSE",
    "descripcion": "ACIDO TRANEXAMICO 500MG",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "PEN DI BEN 2.400.000 U.I",
    "descripcion": "PENICILINA G BENZATINICA",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "PENCAN Nº 27",
    "descripcion": "AGUJA",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "ESTUCHE",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "PENTRAX AC",
    "descripcion": "ANTIBIOTICO",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSB5",
    "nombre": "PERICAN 18GX1/4",
    "descripcion": "AGUJA",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "AGUJA",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDF",
    "nombre": "PIPEBAC T",
    "descripcion": "PIPERACILINA TAZOBACTAM 4.5",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7821",
    "nombre": "PIPERACICLINA MAS TAZOBACTAM 4,5",
    "descripcion": "PIPERACICLINA MAS TAZOBACTAM 4,5",
    "marca": "ARGENTINO",
    "fabricante": "ARGENTINO",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDT",
    "nombre": "PIREDOL 100",
    "descripcion": "PARACETAMOL 100 MG",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "GOTAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB30",
    "nombre": "PIRONAL FLU",
    "descripcion": "IBUPROFENO MG/5ML PSEUDOEFEDRINA 15 MG/5ML SUSPENCION ANTIPIRETIUCO ANALGESICO ANTIINFLAMATORIO DESCONGESTIONA NATE",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB32",
    "nombre": "PIRONAL FORTE",
    "descripcion": "IBUPROFENO 200 MG/5ML ANTIPIRETICO ANALGESICO ANTIINFLAMATORIO SUSPENSION",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "PLATELET",
    "descripcion": "ETAMSILATO 250MG / 2ML",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU63",
    "nombre": "PORTAOBJETO",
    "descripcion": "PORTAOBJETOS",
    "marca": "CHINO",
    "fabricante": "CHINO",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDC",
    "nombre": "POTASIO CL 1.3 MEQ",
    "descripcion": "ANTIHIPOKALEMICO",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDQ5",
    "nombre": "PREBALIN 75",
    "descripcion": "PREGABALINA 75 MG ANALGESICO",
    "marca": "QUIMFA",
    "fabricante": "QUIMFA",
    "unidad": "COMPRIMIDO TRICEPTADO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDISLCH5",
    "nombre": "PREDNISONA LCH",
    "descripcion": "20 MG CORTICOSTEROIDE",
    "marca": "LCH",
    "fabricante": "LCH",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDS10",
    "nombre": "PREDNISONA LCH 5",
    "descripcion": "PREDNISONA 5 MG",
    "marca": "LCH",
    "fabricante": "LCH",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDC",
    "nombre": "PREGABALINA",
    "descripcion": "PREGABALINA 75 MG",
    "marca": "COFAR",
    "fabricante": "COFAR",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "PRESERVATIVO",
    "descripcion": "PRESERVATIVO",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7820",
    "nombre": "PRESTAT 75",
    "descripcion": "PREGABALINA 75",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "PROCIN DIGEST",
    "descripcion": "CINITAPRIDA 1 MG PANCREATINA SIMETICONA",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIIN-B26",
    "nombre": "PROPOFOL LIPURO",
    "descripcion": "PROPOFOL LIPURO 20 ML",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "POM3",
    "nombre": "QUEMACURAN L",
    "descripcion": "POMADA",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7793",
    "nombre": "QUETIAPINA 100MG",
    "descripcion": "ANTIPSICOTICO ATIPICO",
    "marca": "CATEDRAL",
    "fabricante": "CATEDRAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "QUETOROL 30 AMP",
    "descripcion": "QUETOROL 30 MG",
    "marca": "NOVO PHARMA",
    "fabricante": "NOVO PHARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "QUETOROL 30 SL",
    "descripcion": "QUETOROL 30 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "COMPRIMIDO SUBLINGUAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "QUETOROL 30MG",
    "descripcion": "QUETOROL 30 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7817",
    "nombre": "QUETOROL TRAM",
    "descripcion": "ANALGESICO",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "REMITEX",
    "descripcion": "CETIRIZINA 10 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "POM24",
    "nombre": "RIFAMICINA 10MG/ML",
    "descripcion": "ANTIBIOTICO SOLUCION TOPICA 50ML",
    "marca": "QUIMFA",
    "fabricante": "QUIMFA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDM",
    "nombre": "RIXAM 1000",
    "descripcion": "ACIDO TRANEXAMICO 1000 MG",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDM",
    "nombre": "RIXAM 250",
    "descripcion": "ANTIFIBIBRINOLITIC O 250 MG",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDM13",
    "nombre": "RIXAM 250",
    "descripcion": "ANTIFIBIBRINOLITIC O 5 ML",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7780",
    "nombre": "RIXAM 500",
    "descripcion": "AMPOLLA",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "POM13",
    "nombre": "ROXICAINA JALEA",
    "descripcion": "LIDOCAINA CLORHIDRATO GEL 2%",
    "marca": "ROPSHON",
    "fabricante": "ROPSHON",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDS4",
    "nombre": "SALBUTAMOL AEROSOL",
    "descripcion": "SALBUTAMOL 100MCG/DOSIS BRONCODILATADO R",
    "marca": "SAE",
    "fabricante": "SAE",
    "unidad": "AEROSOL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "SEPTICIDE 500",
    "descripcion": "CIPROFLOXACINA 500 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "SEVOFLURANO",
    "descripcion": "SEVOFLURANO 250 ML",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDINT28",
    "nombre": "SIDEAL FORTE INT",
    "descripcion": "CAPSULA",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDINT26",
    "nombre": "SIDERAL FOLIC",
    "descripcion": "30MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SIDERAL ORO",
    "descripcion": "SIDERAL ORO",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "SIGNUM M",
    "descripcion": "SITAGLIPTINA 50 MG METFORMINA 500 MG",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIS1",
    "nombre": "SINALERG 4",
    "descripcion": "CLORFENIRAMINA 4 MG ANTIHISTAMINICO",
    "marca": "SIGMA",
    "fabricante": "SIGMA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "SITEX 100",
    "descripcion": "CEFIXIMA 100 MG/ML",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "SITEX FORTE",
    "descripcion": "CEFIXIMA 200MG/ML",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "SOL FISIOLOGICA 1000ML",
    "descripcion": "SOL FISIOLOGICA 1000 ML",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "SOL FISIOLOGICA 100ML",
    "descripcion": "SOL FISIOLOGICA 100 ML",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL GLUCOSA 10% 1000ML",
    "descripcion": "SOLUCION",
    "marca": "INTI",
    "fabricante": "INTI- BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL GLUCOSA 10% 500 ML",
    "descripcion": "SOL",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL GLUCOSA 5% 1000 ML",
    "descripcion": "SOLUCION GLUCOSA 5%1000 ML",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "SOL GLUCOSA 50% 500 ML",
    "descripcion": "SOL GLUCOSA 50 % 500 ML",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL IRRIGACION 1000 ML",
    "descripcion": "IRRIGACION",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL RINGER LACTATO 1000ML",
    "descripcion": "SOLUCION",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL RINGER LACTATO 500 ML",
    "descripcion": "SOLUCION",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL RINGER NORMAL 1000",
    "descripcion": "SOL RINGER NORMAL 1000 ML",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI",
    "nombre": "SOL RINGER NORMAL 500",
    "descripcion": "SOL RINGER NORMAL 500 ML",
    "marca": "INTI",
    "fabricante": "INTI- BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIIN-B2",
    "nombre": "SOLUCION FISIOLOGICA 500 ML",
    "descripcion": "SOLUCION FISIOLOGICA 0.9% 500 ML",
    "marca": "INTI",
    "fabricante": "BRAUN",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDFS10",
    "nombre": "SOMNO XR",
    "descripcion": "ZOLPIDEMTARTRAT O 12,5 MG COMPRIMIDO",
    "marca": "SAVAL",
    "fabricante": "SAVAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU70",
    "nombre": "SONDA FOLEY Nº 10 LATEX",
    "descripcion": "Nº 10",
    "marca": "WINMED/NEOVAC",
    "fabricante": "WINMED/NEOVAC",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU71",
    "nombre": "SONDA FOLEY Nº 12 LATEX",
    "descripcion": "Nº 12",
    "marca": "WINMED/NEOVAC",
    "fabricante": "WINMED/NEOVAC",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU72",
    "nombre": "SONDA FOLEY Nº 14",
    "descripcion": "Nº 14",
    "marca": "WINMED/NEOVAC",
    "fabricante": "WINMED/NEOVAC",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU73",
    "nombre": "SONDA FOLEY Nº 16 LATEX",
    "descripcion": "Nª16",
    "marca": "WINMED/NEOVAC",
    "fabricante": "WINMED/NEOVAC",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU74",
    "nombre": "SONDA FOLEY Nº 18 LATEX",
    "descripcion": "Nº 18",
    "marca": "WINMED/NEOVAC",
    "fabricante": "WINMED/NEOVAC",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU79",
    "nombre": "SONDA NASOGASTRICA Nº 10",
    "descripcion": "Nº12",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU80",
    "nombre": "SONDA NASOGASTRICA Nº 14",
    "descripcion": "Nº 14",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU82",
    "nombre": "SONDA NASOGASTRICA Nº 18",
    "descripcion": "Nº 18",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU75",
    "nombre": "SONDA NASOGASTRICA Nº 4",
    "descripcion": "Nº 4",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU76",
    "nombre": "SONDA NASOGASTRICA Nº 6 O K33",
    "descripcion": "Nº 6 O K33",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU78",
    "nombre": "SONDA NASOGASTRICA Nº10",
    "descripcion": "Nº10",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU77",
    "nombre": "SONDA NASOGASTRICA Nº8",
    "descripcion": "Nº 8",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU81",
    "nombre": "SONDA NASOGATRICA Nº 16",
    "descripcion": "Nº 16",
    "marca": "SAMED",
    "fabricante": "SAMED",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU122",
    "nombre": "SONDA NELATON Nº 16",
    "descripcion": "SONDA NELATON Nº 16",
    "marca": "WELLEAD",
    "fabricante": "WELLEAD",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDIIN-B39",
    "nombre": "SPINOCAN Nº 22",
    "descripcion": "SPINOCAN Nº 22 AGUJA",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIIN-B40",
    "nombre": "SPINOCAN Nº 25",
    "descripcion": "SPINOCAN Nº 25 AGUJA",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIIN-B41",
    "nombre": "SPINOCAN Nº 26",
    "descripcion": "SPINOCAN Nº 26 AGUJA",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB14",
    "nombre": "STOPER",
    "descripcion": "TAPON HEPARINIZADO",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "ESTUCHE",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU173",
    "nombre": "STYPCEL",
    "descripcion": "ABSORBIBLE HEMOSTATICO",
    "marca": "MEDPRIN",
    "fabricante": "MEDPRIN",
    "unidad": "SOBRES",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDBP",
    "nombre": "SUCRABONAGEL",
    "descripcion": "SUCRALFATO 2GR SIMETICONA 200 MG 200 ML",
    "marca": "BRESKOT PHARMA",
    "fabricante": "BRESKOT PHARMA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDM",
    "nombre": "SUCRALTIP",
    "descripcion": "SUCRALFATO",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDA",
    "nombre": "SUERO DE LA VIDA",
    "descripcion": "SUERO DE REHIDRATACION ORAL",
    "marca": "ALFA",
    "fabricante": "ALFA",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV49",
    "nombre": "SULFATO DE MAGNESIO",
    "descripcion": "SULFATO DE MAGNESIO 10 ML",
    "marca": "PHARMANDINA",
    "fabricante": "PHARMANDINA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SUMAX 50",
    "descripcion": "SUMAX 50",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDTC5",
    "nombre": "SUPRACAM",
    "descripcion": "MELOXICAM 15MG",
    "marca": "TECNOFARMA",
    "fabricante": "TECNOFARMA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDTC6",
    "nombre": "SUPRACAM 15 AMP",
    "descripcion": "MELOXICAM 15 MG AMPOLLA",
    "marca": "TECNOFARMA",
    "fabricante": "TECNOFARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDTC4",
    "nombre": "SUPRACAM FLEX",
    "descripcion": "MELOXICAM/PRIDIN OL 15 MG/4MG ANTIINFLAMATORIO",
    "marca": "TECNOFARMA",
    "fabricante": "TECNOFARMA",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "POM4",
    "nombre": "SUPRACORTIN",
    "descripcion": "CREMA 10 GR",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "T4",
    "descripcion": "LEVOTIROXINA 100 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB39",
    "nombre": "TALFLEX",
    "descripcion": "KETOPROFENO 100MG/2ML ANALGESICO ANTIINFLAMATORIO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "AMPOLLAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX 100",
    "descripcion": "KETOPROFENO 100 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX B1 B6 B12",
    "descripcion": "KETOPROFENO 100MG- TIAMINA 250MG - PIRIDOXINA 250MG - CIANOCOBALAMINA 5.000 UG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX B1B6B12 AMP",
    "descripcion": "ANALGESICO ANTIINFLAMATORIO ANTINEURITICO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX B1B6B12 FORTE",
    "descripcion": "ANALGESICO ANTIINFLAMATORIO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX BI",
    "descripcion": "KETOPROFENO 150",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "TAMBOL",
    "descripcion": "TRAMADOL 100 MG",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "TAMBOL COMP",
    "descripcion": "TRAMADOL",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "TAMBOL FORTE",
    "descripcion": "ANALGESICO",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIPM5",
    "nombre": "TAZAROL",
    "descripcion": "DEXKETOPROFENO 50MG/2ML ANALGESICO ANTIINFLAMATORIO Y ANTIARTROSICO",
    "marca": "LAFAGE",
    "fabricante": "PRO MEDICAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU127",
    "nombre": "TEGADERM",
    "descripcion": "10*12 CM",
    "marca": "3M/",
    "fabricante": "3M/",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDIF",
    "nombre": "TENSIUM 20",
    "descripcion": "OLMESARTAN 20 MG",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "TENSIUM 40",
    "descripcion": "OLMESARTAN 40 MG",
    "marca": "FARMEDICAL",
    "fabricante": "FARMEDICAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "TERBOCAINA 2%",
    "descripcion": "LIDOCAINA 2% 20 ML",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7810",
    "nombre": "TERBOCAINA AMPOLLA INYECTABLE",
    "descripcion": "LIDOCAINA AMPOLLA",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIT4",
    "nombre": "TERBOCYL 6.3.3",
    "descripcion": "PENICILINA G BENZATINICA 600000UI-PENICILINA G SODICA 600000UI-PENICILINA G PROCAINICA 300000UI. ANTIBACTERIANOS SISTEMICOS.",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "VIAL +DILUYENTE`JERINGA +ALCOHOL PAD",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIT5",
    "nombre": "TERBOCYL FORTE",
    "descripcion": "PENICILINA G BENZATINICA 1200000UI-PENICILINA G SODICA 600000UI-PENICILINA G PROCAINICA 600000UI. ANTIBACTERIANOS SISTEMICOS.",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "VIAL+DILUYENTE +JERINGA+ALCOHOL PAD",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV21",
    "nombre": "TERBOMICINA",
    "descripcion": "GENTAMICINA 280 MG/2ML",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV",
    "nombre": "TERMETIK",
    "descripcion": "METOCLOPRAMIDA 10 MG",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "TERMOMETRO DE MERCURIO",
    "descripcion": "TERMOMETRO ORAL",
    "marca": "CLINICAL",
    "fabricante": "CLINICAL",
    "unidad": "TERMOMETRO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU83",
    "nombre": "TESTIGO HUMEDO",
    "descripcion": "CALOR HUMEDO",
    "marca": "CREMER/CHINO",
    "fabricante": "CREMER/CHINO",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU84",
    "nombre": "TESTIGO SECO",
    "descripcion": "CALOR SECO",
    "marca": "CREMER/CHINO",
    "fabricante": "CREMER/CHINO",
    "unidad": "SACHET",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDLF5",
    "nombre": "TIAMIGAL",
    "descripcion": "50MG/ML AVITAMINOSIS B1",
    "marca": "LAQFAGAL",
    "fabricante": "LAQFAGAL",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB1A",
    "nombre": "TIAXAL I M",
    "descripcion": "CEFTRIAXONA 1000 MG",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "VIAL+SOLVENTE +JERINGA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB6",
    "nombre": "TIAXAL IV",
    "descripcion": "CEFTRIAXONA 1000 MG ANTIBIOTICO CEFALOSPORINA DE 3 GENERACION",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "AMPOLLA + SOLVENTE",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIPM9",
    "nombre": "TOBRAZOL",
    "descripcion": "TOBRAMICINA 0,3% SOLUCION OFTALMICA GOTAS OFTALMICAS",
    "marca": "PRO MEDICAL",
    "fabricante": "PRO MEDICAL",
    "unidad": "GOTERO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7790",
    "nombre": "TOCEX JARABE",
    "descripcion": "EXPECTORANTE",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "POM22",
    "nombre": "TOPICREM",
    "descripcion": "CREMA 20G COTRIMAZOL 1G GENTAMICINA 0.1G BETAMETASONA 0.05G",
    "marca": "HERSIL",
    "fabricante": "HERSIL",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "TORNIX 20",
    "descripcion": "ATORVASTATINA 20 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB7",
    "nombre": "TRACUTIL",
    "descripcion": "OLIGOELEMENTOS 10ML",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7801",
    "nombre": "TRANEST ACIDO TRANEXAMICO 500MG/5ML",
    "descripcion": "ACIDO TRANEXAMICO 500MG/5ML",
    "marca": "ALFA",
    "fabricante": "ALFA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB16",
    "nombre": "TRANSOFIX N",
    "descripcion": "TRANSOFIX N UNA PUNTA",
    "marca": "INTI- BRAUN",
    "fabricante": "INTI- BRAUN",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDB",
    "nombre": "TRIAPEN FORTE",
    "descripcion": "ANTIBIOTICO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "TROXOLINA 500",
    "descripcion": "CEFALEXINA 500/5ML",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "TROXOLINA CAP",
    "descripcion": "TROXOLINA 500 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU85",
    "nombre": "TUBO ARMADO N 6.5",
    "descripcion": "Nº 6.5",
    "marca": "WELLEAD",
    "fabricante": "WELLEAD",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU86",
    "nombre": "TUBO ARMADO Nº 7",
    "descripcion": "Nº 7",
    "marca": "WELLEAD",
    "fabricante": "WELLEAD",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MED",
    "nombre": "TUBO DE TRAQUEOSTOMIA 7",
    "descripcion": "TUBO",
    "marca": "SALUR",
    "fabricante": "SALUR",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MED",
    "nombre": "TUBO DE TRAQUEOSTOMIA 7.5",
    "descripcion": "TUBO",
    "marca": "SALUR",
    "fabricante": "SALUR",
    "unidad": "TUBO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "EQUI6767",
    "nombre": "TUBO ENDOTRAQUEAL Nª 7.0 CON BALON",
    "descripcion": "TUBO ENDOTRAQUEAL Nª 7.0 CON BALON",
    "marca": "SAMED/WELLEAD/I NDOTRACHEAL TUBE",
    "fabricante": "SAMED/WELLEAD/ INDOTRACHEAL TUBE",
    "unidad": "PIEZAS",
    "tipo": "EQUIPAMIENTO"
  },
  {
    "codigo": "EQUI6769",
    "nombre": "TUBO ENDOTRAQUEAL Nª7.5 CON BALON",
    "descripcion": "TUBO ENDOTRAQUEAL Nª7.5 CON BALON",
    "marca": "SAMED/WELLEAD/I NDOTRACHEAL TUBE",
    "fabricante": "SAMED/WELLEAD/ INDOTRACHEAL TUBE",
    "unidad": "PIEZAS",
    "tipo": "EQUIPAMIENTO"
  },
  {
    "codigo": "MED",
    "nombre": "TUBO ENDOTRAQUEAL Nº 2.5",
    "descripcion": "TUBO N",
    "marca": "SAMED/WELLEAD/I NDOTRACHEAL TUBE",
    "fabricante": "SAMED/WELLEAD/ INDOTRACHEAL TUBE",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU89",
    "nombre": "TUBO ENDOTRAQUEAL Nº 3",
    "descripcion": "Nº 3 SIN BALON",
    "marca": "SAMED/WELLEAD/I NDOTRACHEAL TUBE",
    "fabricante": "SAMED/WELLEAD/ INDOTRACHEAL TUBE",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU90",
    "nombre": "TUBO ENDOTRAQUEAL Nº 4",
    "descripcion": "Nº 4",
    "marca": "SAMED/WELLEAD/I NDOTRACHEAL TUBE",
    "fabricante": "SAMED/WELLEAD/ INDOTRACHEAL TUBE",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU92",
    "nombre": "TUBO ENDOTRAQUEAL Nº 5",
    "descripcion": "Nº 5 CON BALON",
    "marca": "SAMED/WELLEAD/I NDOTRACHEAL TUBE",
    "fabricante": "SAMED/WELLEAD/ INDOTRACHEAL TUBE",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU93",
    "nombre": "TUBO ENDOTRAQUEAL Nº 5.5",
    "descripcion": "Nº 5.5 CON BALON",
    "marca": "SAMED/WELLEAD/I NDOTRACHEAL TUBE",
    "fabricante": "SAMED/WELLEAD/ INDOTRACHEAL TUBE",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU94",
    "nombre": "TUBO ENDOTRAQUEAL Nº 6",
    "descripcion": "Nº 6 CON BALON",
    "marca": "SAMED/WELLEAD/I NDOTRACHEAL TUBE",
    "fabricante": "SAMED/WELLEAD/ INDOTRACHEAL TUBE",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU95",
    "nombre": "TUBO ENDOTRAQUEAL Nº 6,5",
    "descripcion": "Nº 6.5 CON BALON",
    "marca": "SAMED/WELLEAD/I NDOTRACHEAL TUBE",
    "fabricante": "SAMED/WELLEAD/ INDOTRACHEAL TUBE",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU97",
    "nombre": "TUBO ENDOTRAQUEAL Nº 8",
    "descripcion": "Nº8 CON BALON",
    "marca": "SAMED/WELLEAD/I NDOTRACHEAL TUBE",
    "fabricante": "SAMED/WELLEAD/ INDOTRACHEAL TUBE",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDI7791",
    "nombre": "TUSABRON",
    "descripcion": "ANTITUSIVO",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB35",
    "nombre": "TUSIGEN",
    "descripcion": "CLORFENIRAMINA 2 MG PSEUDOEFEDRINA 30 MG CODEINA 10 MG/5ML JARABE 100 ML ANTITUSIVO DESCONGESTIONA NTE",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIB34",
    "nombre": "TUSIGEN INFANTIL",
    "descripcion": "CLORFENIRAMINA 0.5MG/5ML PSEUDOEFEDRINA 7,5MG/5ML CODEINA 10 MG/5ML JARABE 100 ML ANTITUSIVO DESCONGESTIONA NTE",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDM",
    "nombre": "TUSILEXIL D",
    "descripcion": "ANTITUSIVO ANTIGRIPAL",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI11",
    "nombre": "TUSSINOL",
    "descripcion": "100 ML ANTITUSIVO",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDC3",
    "nombre": "ULTRAVIST 300",
    "descripcion": "IOPROMIDA 300 MG/ML 100 ML",
    "marca": "BAGO",
    "fabricante": "BAGO",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7794",
    "nombre": "ULTRAVIST 300/50ML",
    "descripcion": "IOPROMIDA 50ML",
    "marca": "BAGO BAYER",
    "fabricante": "BAGO BAYER",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7824",
    "nombre": "UROGRAFINA 76%",
    "descripcion": "AMIDOTRIZOATO DE MEGLUMINA",
    "marca": "BAYER",
    "fabricante": "BAYER",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDH",
    "nombre": "UROHAN",
    "descripcion": "ESPIRONOLACTON A 100 MG",
    "marca": "HAHNEMANN",
    "fabricante": "HAHNEMANN",
    "unidad": "TABLETA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7802",
    "nombre": "UVAMIN RETARD 100MG",
    "descripcion": "NITROFURANTOINA CAPSULAS 100MG",
    "marca": "SIGMA",
    "fabricante": "SIGMA",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7797",
    "nombre": "VALAX 160MG",
    "descripcion": "VALSARTAN 160MG",
    "marca": "SAVAL",
    "fabricante": "SAVAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI7798",
    "nombre": "VALAXAM D",
    "descripcion": "VALSARTAN/AMLOD IPINO",
    "marca": "SAVAL",
    "fabricante": "SAVAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDA",
    "nombre": "VANCOMICINA 1GR",
    "descripcion": "ANTIBIOTICO",
    "marca": "ARGENTINO",
    "fabricante": "ARGENTINO",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "VANCOMICINA 500",
    "descripcion": "VIAL 500 MG",
    "marca": "IFA",
    "fabricante": "IFA",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDLQ14",
    "nombre": "VARDACTONE 25",
    "descripcion": "ESPIRONOLACTON A 25 MG DIURETICO",
    "marca": "LAQFAGAL",
    "fabricante": "LAQFAGAL",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU111",
    "nombre": "VENDA COBAN 3\"",
    "descripcion": "3\"",
    "marca": "CHINO",
    "fabricante": "CHINO",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU104",
    "nombre": "VENDA DE GASA 10 CM",
    "descripcion": "10 CM",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU105",
    "nombre": "VENDA DE GASA 15 CM",
    "descripcion": "Nº 15",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MED",
    "nombre": "VENDA DE GASA 20 CM",
    "descripcion": "VENDA",
    "marca": "PREMIER",
    "fabricante": "PREMIER",
    "unidad": "SACHET",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "INSU102",
    "nombre": "VENDA DE GASA 5 CM",
    "descripcion": "5 CM",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU103",
    "nombre": "VENDA DE GASA 7,5 CM",
    "descripcion": "7.5 CM",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU112",
    "nombre": "VENDA DE YESO 10 CM",
    "descripcion": "10 CM",
    "marca": "CREMER",
    "fabricante": "CREMER",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU113",
    "nombre": "VENDA DE YESO 15 CM",
    "descripcion": "15 CM",
    "marca": "CREMER",
    "fabricante": "CREMER",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU114",
    "nombre": "VENDA DE YESO 20 CM",
    "descripcion": "20 CM",
    "marca": "CREMER",
    "fabricante": "CREMER",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU108",
    "nombre": "VENDA ELASTICA 10 CM",
    "descripcion": "Nº 10 CM",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU109",
    "nombre": "VENDA ELASTICA 15 CM",
    "descripcion": "15 CM",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU110",
    "nombre": "VENDA ELASTICA 20 CM",
    "descripcion": "20 CM",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU107",
    "nombre": "VENDA ELASTICA 5 CM",
    "descripcion": "5 CM",
    "marca": "PREMIER/OPTIMED",
    "fabricante": "PREMIER/OPTIME D",
    "unidad": "PIEZAS",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "INSU6759",
    "nombre": "VENDAS DE GASA 10CM",
    "descripcion": "VENDAS DE GASA 10CM",
    "marca": "ALCOA",
    "fabricante": "ALCOA",
    "unidad": "BOLSA",
    "tipo": "INSUMOS"
  },
  {
    "codigo": "MEDM7",
    "nombre": "VIADIL COMPUESTO",
    "descripcion": "ANTIESPASMODICO ANALGESICO",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "PAR DE AMPOLLAS",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIM6",
    "nombre": "VIADIL COMPUESTO NF",
    "descripcion": "ANTIESPASMODICO ANALGESICO",
    "marca": "MEGALABS",
    "fabricante": "MEGALABS",
    "unidad": "COMPRIMIDO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDIN",
    "nombre": "VIRUSAN 500",
    "descripcion": "ACICLOVIR 500 MG",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "VIAL",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV51",
    "nombre": "VITAMINA C",
    "descripcion": "VITAMINA C",
    "marca": "ALFA",
    "fabricante": "ALFA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDT",
    "nombre": "VITAMINA C 2 SOBRE",
    "descripcion": "VITAMINA C 2000 MG",
    "marca": "TERBOL",
    "fabricante": "TERBOL",
    "unidad": "SOBRES",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "AYV",
    "nombre": "VITAMINA K",
    "descripcion": "VITAMINA K 10 MG",
    "marca": "ALFA DISMEDIN",
    "fabricante": "ALFA DISMEDIN",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI6764",
    "nombre": "VITAMINA K MUNDO PHARMA",
    "descripcion": "VITAMINA K",
    "marca": "MUNDO PHARMA",
    "fabricante": "MUNDO PHARMA",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDF",
    "nombre": "XAMIC",
    "descripcion": "ACIDO TRANEXAMICO 500 MG",
    "marca": "FORTIER",
    "fabricante": "FORTIER",
    "unidad": "AMPOLLA",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDI",
    "nombre": "ZINC VIMIN",
    "descripcion": "ZINC VIMIN 20 MG/ML",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "FRASCO",
    "tipo": "FARMACIA"
  },
  {
    "codigo": "MEDINT20",
    "nombre": "ZOLION RELAX",
    "descripcion": "LACTOBACILLUS PLATARUM",
    "marca": "INTI",
    "fabricante": "INTI",
    "unidad": "CAPSULA",
    "tipo": "FARMACIA"
  }
]
JSON, true);

        foreach ($fabricantes as $nombre) {
            DB::table('fabricantes')->updateOrInsert(
                ['nombre' => $nombre],
                ['pais' => null, 'deleted_at' => null, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        foreach ($unidades as $unidad) {
            DB::table('unidades')->updateOrInsert(
                ['nombre' => $unidad['nombre']],
                ['abreviatura' => $unidad['abreviatura'], 'deleted_at' => null, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $fabricanteIds = DB::table('fabricantes')->pluck('id', 'nombre')->all();
        $unidadIds = DB::table('unidades')->pluck('id', 'nombre')->all();

        foreach ($productos as $producto) {
            DB::table('productos')->updateOrInsert(
                [
                    'codigo' => $producto['codigo'],
                    'nombre' => $producto['nombre'],
                ],
                [
                    'descripcion' => $producto['descripcion'],
                    'marca' => $producto['marca'],
                    'fabricante_id' => $fabricanteIds[$producto['fabricante']] ?? null,
                    'unidad_id' => $unidadIds[$producto['unidad']] ?? null,
                    'tipo' => 'FARMACIA',
                    'deleted_at' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $productos = json_decode(<<<'JSON'
[
  {
    "codigo": "MEDI7813",
    "nombre": "ABRILAR JARABE"
  },
  {
    "codigo": "MEDINT30",
    "nombre": "ACD VIMIN"
  },
  {
    "codigo": "MEDIC7",
    "nombre": "ACETILCISTEINA 200"
  },
  {
    "codigo": "MEDI7773",
    "nombre": "ACETILCISTEINA 300MG/3ML AMPOLLA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ACICLOVIR 400"
  },
  {
    "codigo": "MEDBP",
    "nombre": "ACTICAP 400"
  },
  {
    "codigo": "MEDBP",
    "nombre": "ACTICAP 600"
  },
  {
    "codigo": "MEDIBB2",
    "nombre": "ACTRON"
  },
  {
    "codigo": "MEDIN",
    "nombre": "ACUPAM"
  },
  {
    "codigo": "MEDI7771",
    "nombre": "ADECUAN AMPOLLA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ADRENALINA"
  },
  {
    "codigo": "INSU115",
    "nombre": "AEROCAMARA"
  },
  {
    "codigo": "MEDM9",
    "nombre": "AERONID 50"
  },
  {
    "codigo": "INSU1",
    "nombre": "AGUA OXIGENADA"
  },
  {
    "codigo": "INSU2",
    "nombre": "AGUA OXIGENADA"
  },
  {
    "codigo": "AYV",
    "nombre": "AGUA PARA INYECCION"
  },
  {
    "codigo": "INSU6",
    "nombre": "AGUJA HIPODERMICA"
  },
  {
    "codigo": "EQUI6770",
    "nombre": "AGUJA HIPODERMICA 22G 1/2"
  },
  {
    "codigo": "MEDI7804",
    "nombre": "AGUJA HIPODERMICA 23G OPTIMED"
  },
  {
    "codigo": "INSU7",
    "nombre": "AGUJA HIPODERMICA 23G X 1 1/2"
  },
  {
    "codigo": "INSU9",
    "nombre": "AGUJA HIPODERMICA 30G X 1/2\""
  },
  {
    "codigo": "INSU3",
    "nombre": "AGUJA HIPODERMICA Nº 18G X1 1/2\""
  },
  {
    "codigo": "INSU5",
    "nombre": "AGUJA HIPODERMICA Nº 21 G X1 1/2\""
  },
  {
    "codigo": "INSU4",
    "nombre": "AGUJA HIPODERMICA Nº 21G X1\""
  },
  {
    "codigo": "MEDIF",
    "nombre": "ALBENDAZOL 200"
  },
  {
    "codigo": "MEDI7799",
    "nombre": "ALBUMINA HUMANA 20%"
  },
  {
    "codigo": "MEDM",
    "nombre": "ALBURX"
  },
  {
    "codigo": "MEDI7824",
    "nombre": "ALFA B1"
  },
  {
    "codigo": "MEDA2",
    "nombre": "ALFA PERIDOL"
  },
  {
    "codigo": "INSU11",
    "nombre": "ALGODON 100 GR"
  },
  {
    "codigo": "MED",
    "nombre": "ALGODON 400 GR"
  },
  {
    "codigo": "MEDF",
    "nombre": "ALIVIOL ANTIGRIPAL CAP COLORES"
  },
  {
    "codigo": "POM12",
    "nombre": "ALIVIOL GEL FORTE"
  },
  {
    "codigo": "MEDF",
    "nombre": "ALIVIOL PLUS"
  },
  {
    "codigo": "MEDIIN-B24",
    "nombre": "AMINOPLASMAL"
  },
  {
    "codigo": "MEDA",
    "nombre": "AMIODORONA"
  },
  {
    "codigo": "MEDI8",
    "nombre": "AMITRIPTILINA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "AMLODIPINA 10"
  },
  {
    "codigo": "MEDFS1",
    "nombre": "AMOVAL"
  },
  {
    "codigo": "MEDC",
    "nombre": "AMOXICILINA 1"
  },
  {
    "codigo": "MEDC",
    "nombre": "AMOXICILINA 500 MG"
  },
  {
    "codigo": "MEDF",
    "nombre": "AMOXIDIN 250"
  },
  {
    "codigo": "MEDF",
    "nombre": "AMOXIDIN 500"
  },
  {
    "codigo": "MEDF",
    "nombre": "AMOXIDIN PLUS FORTE"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "AMPICILINA"
  },
  {
    "codigo": "MEDPR9",
    "nombre": "ANESTEARS"
  },
  {
    "codigo": "MEDIN",
    "nombre": "ANTIGRIPAL COMPUESTO"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ASA"
  },
  {
    "codigo": "MED",
    "nombre": "ATORVASTATINA"
  },
  {
    "codigo": "MED",
    "nombre": "ATRACURIO"
  },
  {
    "codigo": "AYV5",
    "nombre": "ATROPINA"
  },
  {
    "codigo": "MEDH",
    "nombre": "AZITROMICINA 1"
  },
  {
    "codigo": "MEDIF",
    "nombre": "B VIMIN 300"
  },
  {
    "codigo": "MEDIFA",
    "nombre": "BACITRACINA NEOMICINA"
  },
  {
    "codigo": "MEDIB 25",
    "nombre": "BACTICEL FORTE"
  },
  {
    "codigo": "MEDI7788",
    "nombre": "BACTICEL FORTE SUSPENSION"
  },
  {
    "codigo": "MEDI7789",
    "nombre": "BACTICEL SUSPENSION"
  },
  {
    "codigo": "MEDB",
    "nombre": "BAGO VITAL DIGEST"
  },
  {
    "codigo": "INSU13",
    "nombre": "BAJALENGUA ADULTO"
  },
  {
    "codigo": "INSU14",
    "nombre": "BAJALENGUA PEDIATRICO"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BECOR RAPILENTO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "BETACLOX 1 GR"
  },
  {
    "codigo": "MEDI7776",
    "nombre": "BETISTIN"
  },
  {
    "codigo": "AYV5",
    "nombre": "BICARBONATO DE SODIO"
  },
  {
    "codigo": "MED",
    "nombre": "BICERTO 150"
  },
  {
    "codigo": "MEDIN6",
    "nombre": "BILISAN 100"
  },
  {
    "codigo": "MEDIN",
    "nombre": "BIO ELECTRO"
  },
  {
    "codigo": "MEDS6",
    "nombre": "BIOCICATRIZANTE"
  },
  {
    "codigo": "MEDIBPH7",
    "nombre": "BIOTIX"
  },
  {
    "codigo": "INSU16",
    "nombre": "BISTURI Nº 11"
  },
  {
    "codigo": "INSU17",
    "nombre": "BISTURI Nº 15"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BLOKTUS NATURAL"
  },
  {
    "codigo": "MEDB22",
    "nombre": "BOLSA DE COLOSTOMIA"
  },
  {
    "codigo": "INSU22",
    "nombre": "BOLSA DE ORINA NIPRO"
  },
  {
    "codigo": "INSU20",
    "nombre": "BOLSA DE ORINA NORMAL"
  },
  {
    "codigo": "INSU21",
    "nombre": "BOLSA DE ORINA PEDIATRICA"
  },
  {
    "codigo": "POM1",
    "nombre": "BONABEN LOCION"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BONAGEL"
  },
  {
    "codigo": "MEDBP",
    "nombre": "BONAGEL PLUS"
  },
  {
    "codigo": "INSU23",
    "nombre": "BRANULA Nº 14"
  },
  {
    "codigo": "INSU25",
    "nombre": "BRANULA Nº 18"
  },
  {
    "codigo": "INSU26",
    "nombre": "BRANULA Nº 20"
  },
  {
    "codigo": "INSU27",
    "nombre": "BRANULA Nº 22"
  },
  {
    "codigo": "INSU28",
    "nombre": "BRANULA Nº24"
  },
  {
    "codigo": "MEDIF",
    "nombre": "BRONCOFLU"
  },
  {
    "codigo": "MEDS7",
    "nombre": "BROXMOL MR"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "BUPIGOBBI 0.5% HIPERBARICA"
  },
  {
    "codigo": "AYV8",
    "nombre": "BUPIVACAINA 0,5% 10 ML"
  },
  {
    "codigo": "MEDIPM7",
    "nombre": "BUTAMOL"
  },
  {
    "codigo": "AYV10",
    "nombre": "BUTIL BROMURO HIOSCINA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "C TRIX"
  },
  {
    "codigo": "MEDIV",
    "nombre": "C VIMIN + ZINC"
  },
  {
    "codigo": "INSU31",
    "nombre": "CANULA DE ASPIRACION Nº 12"
  },
  {
    "codigo": "INSU34",
    "nombre": "CANULA DE ASPIRACION Nº 18"
  },
  {
    "codigo": "INS29-1",
    "nombre": "CANULA DE ASPIRACION Nº 6"
  },
  {
    "codigo": "INSU29",
    "nombre": "CANULA DE ASPIRACION Nº 8"
  },
  {
    "codigo": "INSU32",
    "nombre": "CANULA DE ASPIRACION Nº14"
  },
  {
    "codigo": "INSU33",
    "nombre": "CANULA DE ASPIRACION Nº16"
  },
  {
    "codigo": "MEDISLCH1",
    "nombre": "CARBAMAZEPINA LCH"
  },
  {
    "codigo": "MEDIV",
    "nombre": "CARDIO VIMIN"
  },
  {
    "codigo": "MEDISLCH2",
    "nombre": "CARVEDILOL 12,5"
  },
  {
    "codigo": "MEDISLCH6",
    "nombre": "CARVEDILOL 6.25"
  },
  {
    "codigo": "MEDH",
    "nombre": "CEFACRIS 500"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CEFALEXINA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFAZOHAN"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CEFIXIMA"
  },
  {
    "codigo": "MEDT",
    "nombre": "CEFOTAXIM 1"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CEFOTAXIMA"
  },
  {
    "codigo": "AYV13",
    "nombre": "CEFTADIZIMA"
  },
  {
    "codigo": "MEDI15",
    "nombre": "CEFTRIAX 1000"
  },
  {
    "codigo": "MEDI7809",
    "nombre": "CEFTRIAXON 1GR"
  },
  {
    "codigo": "MEDI7796",
    "nombre": "CELEPID 20%"
  },
  {
    "codigo": "MEDI",
    "nombre": "CEPILLO CITOLOGICO"
  },
  {
    "codigo": "INSU172",
    "nombre": "CERA DE HUESO"
  },
  {
    "codigo": "MEDIIN-B45",
    "nombre": "CERTOFIX DUO PAED S 413"
  },
  {
    "codigo": "INSU7818",
    "nombre": "CERTOFIX DUO S720"
  },
  {
    "codigo": "INSU7818",
    "nombre": "CERTOFIX TRIO S720"
  },
  {
    "codigo": "MEDM",
    "nombre": "CEUMID 500"
  },
  {
    "codigo": "MEDM",
    "nombre": "CEUMID 500 COMP"
  },
  {
    "codigo": "MEDI7830",
    "nombre": "CIPRODEX"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CIPROFLOXACINA"
  },
  {
    "codigo": "AYV15",
    "nombre": "CIPROFLOXACINA"
  },
  {
    "codigo": "INSU123",
    "nombre": "CLAMP UMBILICAL"
  },
  {
    "codigo": "AYV14",
    "nombre": "CLINDALCOS"
  },
  {
    "codigo": "INSU124",
    "nombre": "CLIP"
  },
  {
    "codigo": "MEDIB20",
    "nombre": "CLOFENAC 75"
  },
  {
    "codigo": "MEDIB9",
    "nombre": "CLOFENAC 75"
  },
  {
    "codigo": "MEDIB8",
    "nombre": "CLOFENAC RELAX"
  },
  {
    "codigo": "MEDI7814",
    "nombre": "CLOFENAC RELAX COMPRIMIDO"
  },
  {
    "codigo": "MEDI7805",
    "nombre": "CLOFENAC RELAX FORTE X CAPSULAS"
  },
  {
    "codigo": "MEDI7792",
    "nombre": "CLOFEXAN FORTE"
  },
  {
    "codigo": "MEDB",
    "nombre": "CLOFEXAN RELAX FORTE"
  },
  {
    "codigo": "MEDB",
    "nombre": "CLOFEXAN50"
  },
  {
    "codigo": "MEDC",
    "nombre": "CLOPIDOGREL"
  },
  {
    "codigo": "POM23",
    "nombre": "CLORANFENICOL 1%"
  },
  {
    "codigo": "MEDIS6",
    "nombre": "CLOREX"
  },
  {
    "codigo": "AYV17",
    "nombre": "CLORFERINAMINA"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "CLORURO DE POTASIO"
  },
  {
    "codigo": "AYV19",
    "nombre": "CLORURO DE SODIO"
  },
  {
    "codigo": "MEDI",
    "nombre": "CLORURO DE SODIO 10"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CLOTRIM 1%"
  },
  {
    "codigo": "MEDIV",
    "nombre": "COBA VIMIN 25000"
  },
  {
    "codigo": "MEDIF",
    "nombre": "CODEINA 10 MG"
  },
  {
    "codigo": "AYV40",
    "nombre": "COFALGINA"
  },
  {
    "codigo": "MED",
    "nombre": "COLINA"
  },
  {
    "codigo": "MED",
    "nombre": "COLLARIN S M L"
  },
  {
    "codigo": "MEDCR",
    "nombre": "COMPAZ"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "COMPLEJO B AMP"
  },
  {
    "codigo": "MEDIV",
    "nombre": "COMPLEJO B VIMIN"
  },
  {
    "codigo": "MEDIV",
    "nombre": "COMPLEJO B VIMIN COMP"
  },
  {
    "codigo": "INSU35",
    "nombre": "CONECTOR CONTRASTE"
  },
  {
    "codigo": "MEDM",
    "nombre": "CORENTEL 5"
  },
  {
    "codigo": "MEDIBPH25",
    "nombre": "CORTIMED 8"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTISTAMIN"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTISTAMIN FORTE"
  },
  {
    "codigo": "MEDB",
    "nombre": "CORTYPIREN"
  },
  {
    "codigo": "MEDI7785",
    "nombre": "CORTYPIREN GOTAS"
  },
  {
    "codigo": "MEDIF",
    "nombre": "COTRIMOXAZOL"
  },
  {
    "codigo": "MEDINT13",
    "nombre": "COTRIZOL FORTE"
  },
  {
    "codigo": "MEDBP",
    "nombre": "CRONOBECOR"
  },
  {
    "codigo": "MEDIB3",
    "nombre": "CRONOCORTEROID"
  },
  {
    "codigo": "EQUI7781",
    "nombre": "CUBRE CALZADOS"
  },
  {
    "codigo": "INSU36",
    "nombre": "CUBREZAPATOS QX"
  },
  {
    "codigo": "FRASCO5",
    "nombre": "CURADIL 90"
  },
  {
    "codigo": "MEDIM2",
    "nombre": "DAPAGLICIN 10"
  },
  {
    "codigo": "MEDF",
    "nombre": "DAPAMET 10/1000"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DEHIDROLIT 75"
  },
  {
    "codigo": "FRASCO3",
    "nombre": "DEHIDROLIT S 75"
  },
  {
    "codigo": "MEDI",
    "nombre": "DEMOTIL AG"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DEMOTIL AMPOLLA"
  },
  {
    "codigo": "MEDI",
    "nombre": "DEMOTIL GOTAS"
  },
  {
    "codigo": "MEDM",
    "nombre": "DEOFLORA"
  },
  {
    "codigo": "POM7",
    "nombre": "DERMOTRIZINC"
  },
  {
    "codigo": "AYV23",
    "nombre": "DEXACOFAZONA 4"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DEXACOFAZONA 8"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DEXALIVIUM"
  },
  {
    "codigo": "MEDI6760",
    "nombre": "DEXAMETASONA OFTALMICA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "DEXAMINO FUERTE"
  },
  {
    "codigo": "MEDI7823",
    "nombre": "DEXAMINO FUERTE AMPOLLA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "DEXAMINO ORAL"
  },
  {
    "codigo": "MEDH",
    "nombre": "DEXMEDETOMIDINA 100MCG"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DEXTROMETORFAN O 10 MG"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DICLOFENACO 1%"
  },
  {
    "codigo": "MEDH",
    "nombre": "DICLOFENACO 50"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DICLOFENACO 75"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DICLOXACILINA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DICLOXACILINA 250 MG"
  },
  {
    "codigo": "MEDIBPH9",
    "nombre": "DIGESTOGAS 300"
  },
  {
    "codigo": "MEDISLCH3",
    "nombre": "DIGOXINA LCH"
  },
  {
    "codigo": "0",
    "nombre": "DIOXADOL"
  },
  {
    "codigo": "MEDIB10",
    "nombre": "DIOXADOL"
  },
  {
    "codigo": "MEDIB11",
    "nombre": "DIOXADOL FORTE"
  },
  {
    "codigo": "MEDIB26",
    "nombre": "DIOXADOL G"
  },
  {
    "codigo": "MEDIF",
    "nombre": "DIPIN 20"
  },
  {
    "codigo": "MEDINT3",
    "nombre": "DIPOSAN 100"
  },
  {
    "codigo": "MEDI7807",
    "nombre": "DIPROFEN 400MG CAPSULAS BLANDAS"
  },
  {
    "codigo": "MEDI7808",
    "nombre": "DIPROFEN 600MG CAPSULAS BLANDAS"
  },
  {
    "codigo": "MEDB20",
    "nombre": "DISCOFIX C"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "DISIDOL 60"
  },
  {
    "codigo": "MEDI4",
    "nombre": "DIURENYL"
  },
  {
    "codigo": "MEDA",
    "nombre": "DOBUTAMINA 12.5 ML"
  },
  {
    "codigo": "MEDIBPH20",
    "nombre": "DOLOCOFAMIN 25.000 B12 FORTE"
  },
  {
    "codigo": "POM10",
    "nombre": "DOLOCOFAMIN 5%"
  },
  {
    "codigo": "MEDBP",
    "nombre": "DOLOFLEXICAM"
  },
  {
    "codigo": "MEDI9",
    "nombre": "DOLOGRIP INFANTIL"
  },
  {
    "codigo": "MEDITC1",
    "nombre": "DOMPER"
  },
  {
    "codigo": "MED",
    "nombre": "DOMPER DIGEST"
  },
  {
    "codigo": "MEDF",
    "nombre": "DORFLEX"
  },
  {
    "codigo": "MEDF",
    "nombre": "DORFLEX AMP"
  },
  {
    "codigo": "MEDIN",
    "nombre": "DYPIRETIC"
  },
  {
    "codigo": "MEDH",
    "nombre": "ECHINACEA COMPLEX"
  },
  {
    "codigo": "MEDI6766",
    "nombre": "ELECTRODO ADULTO"
  },
  {
    "codigo": "INSU126",
    "nombre": "ELECTRODOS PEDIATRICO"
  },
  {
    "codigo": "MEDF",
    "nombre": "EMPAGLYP 25"
  },
  {
    "codigo": "MEDIF",
    "nombre": "ENCIFER"
  },
  {
    "codigo": "MED",
    "nombre": "ENEMA VIT"
  },
  {
    "codigo": "MEDI3",
    "nombre": "ENOXPRIM 20"
  },
  {
    "codigo": "MEDT",
    "nombre": "ENTEROCOLIN"
  },
  {
    "codigo": "INSU38",
    "nombre": "EQUIPO ARCOMED AG"
  },
  {
    "codigo": "EQUI7815",
    "nombre": "EQUIPO DE VENOCLISIS"
  },
  {
    "codigo": "MEDLQ7",
    "nombre": "ERGO 0.2"
  },
  {
    "codigo": "MEDLQ6",
    "nombre": "ERGO INYECTABLE"
  },
  {
    "codigo": "MED",
    "nombre": "ESPARADRAPO"
  },
  {
    "codigo": "MEDI7784",
    "nombre": "ESPASMO DIOXADOL GOTAS"
  },
  {
    "codigo": "MEDB",
    "nombre": "ESPASMO-DIOXADOL PLUS"
  },
  {
    "codigo": "MEDB",
    "nombre": "ESPASMODIOXADOL PLUS"
  },
  {
    "codigo": "MEDIBPH18",
    "nombre": "ESPASMOLOXADIM"
  },
  {
    "codigo": "MEDIBPH16",
    "nombre": "ESPASMOLOXADIM FORTE"
  },
  {
    "codigo": "MEDBP",
    "nombre": "ESPASMOLOXADIM FORTE COMP"
  },
  {
    "codigo": "INSU40",
    "nombre": "ESPATULA CERVICAL"
  },
  {
    "codigo": "MED",
    "nombre": "ESPECULO DESECHABLE S M L"
  },
  {
    "codigo": "MED",
    "nombre": "ESPONJA HEMOSTATICA"
  },
  {
    "codigo": "MEDI6771",
    "nombre": "ETILEFRINA CLORHIDRATO 10MG"
  },
  {
    "codigo": "MEDIM4",
    "nombre": "EUKENE 40"
  },
  {
    "codigo": "MEDI7800",
    "nombre": "EUTIROX 100MCG"
  },
  {
    "codigo": "MEDIN",
    "nombre": "EUTIROX 50"
  },
  {
    "codigo": "MEDIPM2",
    "nombre": "EVIGAX FORTE"
  },
  {
    "codigo": "MEDIIN-B32",
    "nombre": "EXADROP"
  },
  {
    "codigo": "MEDB15",
    "nombre": "EXTENSOFIX 120CM"
  },
  {
    "codigo": "MEDLQ3",
    "nombre": "FENITOGAL"
  },
  {
    "codigo": "0",
    "nombre": "FENITOINA 100"
  },
  {
    "codigo": "MEDA",
    "nombre": "FENITOINA 100AMP"
  },
  {
    "codigo": "MED",
    "nombre": "FENTANILO 0.5"
  },
  {
    "codigo": "MED",
    "nombre": "FILINAR G"
  },
  {
    "codigo": "INSU41",
    "nombre": "FILTRO ANTIBACTERIANO"
  },
  {
    "codigo": "MEDBP",
    "nombre": "FIXIM 100"
  },
  {
    "codigo": "MEDB",
    "nombre": "FIXIM 400"
  },
  {
    "codigo": "MEDBP",
    "nombre": "FIXIM FORTE"
  },
  {
    "codigo": "MEDI4",
    "nombre": "FLAMADIN B12"
  },
  {
    "codigo": "MEDI5",
    "nombre": "FLAMADIN B12 FORTE"
  },
  {
    "codigo": "MEDI7",
    "nombre": "FLAMADIN PLUS"
  },
  {
    "codigo": "AYV",
    "nombre": "FLAMAX"
  },
  {
    "codigo": "MED",
    "nombre": "FLAVO CKR"
  },
  {
    "codigo": "MEDIBPH15",
    "nombre": "FLEXICAM B12 FORTE"
  },
  {
    "codigo": "MEDBP",
    "nombre": "FLEXICAM RELAX SL"
  },
  {
    "codigo": "MEDI6689",
    "nombre": "FLOGIATRIN AMPOLLA"
  },
  {
    "codigo": "MEDI6690",
    "nombre": "FLOGIATRIN POMADA"
  },
  {
    "codigo": "MEDI7812",
    "nombre": "FLOGOCOX 90"
  },
  {
    "codigo": "MEDIN",
    "nombre": "FLORESTOR"
  },
  {
    "codigo": "MEDAYV1",
    "nombre": "FLUICETIL"
  },
  {
    "codigo": "MEDBP",
    "nombre": "FLUIDIMED PRO"
  },
  {
    "codigo": "MEDISLCH7",
    "nombre": "FLUOXETINA LCH"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "FLUOXOL"
  },
  {
    "codigo": "MED",
    "nombre": "FORACORT"
  },
  {
    "codigo": "MED",
    "nombre": "FORFIG 300"
  },
  {
    "codigo": "MEDF",
    "nombre": "FORTICAM 3B"
  },
  {
    "codigo": "MEDQ4",
    "nombre": "FORTINIL 1000"
  },
  {
    "codigo": "INSU43",
    "nombre": "FRASCO DE HECES"
  },
  {
    "codigo": "INSU42",
    "nombre": "FRASCO DE ORINA"
  },
  {
    "codigo": "MEDI7783",
    "nombre": "FUROSEMIDA 20MG/2ML"
  },
  {
    "codigo": "MEDI11",
    "nombre": "GABENOL 1"
  },
  {
    "codigo": "MEDI12",
    "nombre": "GABENOL 2"
  },
  {
    "codigo": "MEDLQ1",
    "nombre": "GALAMINOFIL 250"
  },
  {
    "codigo": "MEDIF",
    "nombre": "GANEUM 150"
  },
  {
    "codigo": "MEDIF",
    "nombre": "GANEUM 75"
  },
  {
    "codigo": "OTRO7777",
    "nombre": "GASA 100 YARDAS"
  },
  {
    "codigo": "MEDF",
    "nombre": "GASSTOP"
  },
  {
    "codigo": "MEDI7822",
    "nombre": "GELBRONQUIAL"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "GENTACOFAR"
  },
  {
    "codigo": "MEDIPM8",
    "nombre": "GENTAMICINA 0.3%"
  },
  {
    "codigo": "MEDI6762",
    "nombre": "GENTAMICINA 80ML"
  },
  {
    "codigo": "MEDB",
    "nombre": "GLICENEX 500"
  },
  {
    "codigo": "MEDB",
    "nombre": "GLICENEX DUO 500/5"
  },
  {
    "codigo": "MED",
    "nombre": "GLUCOCINTAS"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "GLUCONATO DE CALCIO 10%"
  },
  {
    "codigo": "MEDIIN-B12",
    "nombre": "GLUCOSA 20% 500 ML"
  },
  {
    "codigo": "AYV30",
    "nombre": "GLUCOSA 33%"
  },
  {
    "codigo": "SOL",
    "nombre": "GLUCOSA 5% 500 ML"
  },
  {
    "codigo": "MEDI6696",
    "nombre": "GLUCOSA 50% 500ML"
  },
  {
    "codigo": "MEDAYV4",
    "nombre": "GLUMIKIN"
  },
  {
    "codigo": "MEDI7811",
    "nombre": "GOLPEX SPRAY"
  },
  {
    "codigo": "INSU45",
    "nombre": "GORROS QUIRURGICOS"
  },
  {
    "codigo": "MEDSF",
    "nombre": "GRAVOL"
  },
  {
    "codigo": "MEDIM5",
    "nombre": "GRIPETIL"
  },
  {
    "codigo": "INSU47",
    "nombre": "GUANTES DE LATEX M"
  },
  {
    "codigo": "INSU46",
    "nombre": "GUANTES DE LATEX S"
  },
  {
    "codigo": "INSU49",
    "nombre": "GUANTES ESTERILES Nº 6 1/2"
  },
  {
    "codigo": "MEDI",
    "nombre": "GUANTES ESTERILES Nº 6 G"
  },
  {
    "codigo": "INSU50",
    "nombre": "GUANTES ESTERILES Nº 7"
  },
  {
    "codigo": "INSU51",
    "nombre": "GUANTES ESTERILES Nº 7 1/2"
  },
  {
    "codigo": "INSU52",
    "nombre": "GUANTES ESTERILES Nº8"
  },
  {
    "codigo": "MED",
    "nombre": "HEMOVAC Nº 14"
  },
  {
    "codigo": "MED",
    "nombre": "HEMOVAC Nº 16"
  },
  {
    "codigo": "MEDIF",
    "nombre": "HEPARINA SODICA"
  },
  {
    "codigo": "FRASCO1",
    "nombre": "HIDRATA ABD 75"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "HIDROCORTIZONA 100"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "HIDROCORTIZONA 250"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "HIDROCORTIZONA 500"
  },
  {
    "codigo": "INSU150",
    "nombre": "HILO CAT GUT CROMADO 0"
  },
  {
    "codigo": "INSU151",
    "nombre": "HILO CAT GUT CROMADO 1"
  },
  {
    "codigo": "INSU154",
    "nombre": "HILO CAT GUT CROMADO 2"
  },
  {
    "codigo": "INSU",
    "nombre": "HILO CAT GUT CROMADO 2/0"
  },
  {
    "codigo": "INSU155",
    "nombre": "HILO CAT GUT CROMADO 3/0"
  },
  {
    "codigo": "INSU156",
    "nombre": "HILO CAT GUT CROMADO 4/0"
  },
  {
    "codigo": "INSU157",
    "nombre": "HILO CAT GUT CROMADO 5/0"
  },
  {
    "codigo": "INSU145",
    "nombre": "HILO CAT GUT SIMPLE 0"
  },
  {
    "codigo": "INSU146",
    "nombre": "HILO CAT GUT SIMPLE 1"
  },
  {
    "codigo": "INSU147",
    "nombre": "HILO CAT GUT SIMPLE 2/0"
  },
  {
    "codigo": "INSU148",
    "nombre": "HILO CAT GUT SIMPLE 3/0"
  },
  {
    "codigo": "INSU149",
    "nombre": "HILO CAT GUT SIMPLE 4/0"
  },
  {
    "codigo": "MEDI",
    "nombre": "HILO CAT GUT SIMPLE 5/0"
  },
  {
    "codigo": "INSU141",
    "nombre": "HILO NYLON 3/0"
  },
  {
    "codigo": "INSU142",
    "nombre": "HILO NYLON 4/0"
  },
  {
    "codigo": "INSU144",
    "nombre": "HILO NYLON 6/0"
  },
  {
    "codigo": "INSU138",
    "nombre": "HILO NYLON Nº 1"
  },
  {
    "codigo": "INSU140",
    "nombre": "HILO NYLON Nº 2/0"
  },
  {
    "codigo": "INSU143",
    "nombre": "HILO NYLON Nº 5/0"
  },
  {
    "codigo": "MEDI7806",
    "nombre": "HILO POLIPROPILENO 3-0 C/AGUJA MR25 X 75CM"
  },
  {
    "codigo": "INSU",
    "nombre": "HILO POLIPROPILENO 4"
  },
  {
    "codigo": "INSU169",
    "nombre": "HILO POLIPROPILENO 5"
  },
  {
    "codigo": "INSU159",
    "nombre": "HILO SEDA 1"
  },
  {
    "codigo": "EQUI7803",
    "nombre": "HILO SEDA 2/0"
  },
  {
    "codigo": "INSU161",
    "nombre": "HILO SEDA 3/0"
  },
  {
    "codigo": "INSU162",
    "nombre": "HILO SEDA 3/0"
  },
  {
    "codigo": "INSU163",
    "nombre": "HILO SEDA 4/0"
  },
  {
    "codigo": "INSU164",
    "nombre": "HILO SEDA 5/0"
  },
  {
    "codigo": "INSU165",
    "nombre": "HILO SEDA NEGRA 0"
  },
  {
    "codigo": "EQUI7769",
    "nombre": "HILO SEDA NEGRA 0 CARRETA DE 100 YARDAS"
  },
  {
    "codigo": "INSU170",
    "nombre": "HILO SEDA NEGRA 1"
  },
  {
    "codigo": "INSU171",
    "nombre": "HILO SEDA NEGRA 2"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL N 0"
  },
  {
    "codigo": "EQUI7770",
    "nombre": "HILO VICRYL N 0 CON AGUJA 40"
  },
  {
    "codigo": "MEDI6765",
    "nombre": "HILO VICRYL Nª 1 AGUJA 40"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 1"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 2/0"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 3/0"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 4/0"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 5/0"
  },
  {
    "codigo": "MED",
    "nombre": "HILO VICRYL Nº 6/0"
  },
  {
    "codigo": "MEDIN",
    "nombre": "HIPOPRES 20"
  },
  {
    "codigo": "MEDA3",
    "nombre": "HUESOBONE"
  },
  {
    "codigo": "MEDB",
    "nombre": "IBL 1500"
  },
  {
    "codigo": "MEDSF",
    "nombre": "IBUFLAMAR P"
  },
  {
    "codigo": "MEDI7828",
    "nombre": "IBUFORT DUO"
  },
  {
    "codigo": "MEDH",
    "nombre": "IBUMIGRAM"
  },
  {
    "codigo": "MEDC",
    "nombre": "IBUPROFENO 100"
  },
  {
    "codigo": "MEDC",
    "nombre": "IBUPROFENO 200"
  },
  {
    "codigo": "MEDIF",
    "nombre": "IBUPROFENO 400 COMP"
  },
  {
    "codigo": "MEDIF",
    "nombre": "IBUPROFENO 600 COMP"
  },
  {
    "codigo": "MEDIT3",
    "nombre": "IBUPRONAL 400 MG"
  },
  {
    "codigo": "MEDI14",
    "nombre": "IFOTAXIMA 1000"
  },
  {
    "codigo": "MEDI7774",
    "nombre": "IMIPENEM CILASTATINA"
  },
  {
    "codigo": "MEDIBPH8",
    "nombre": "INHIBID"
  },
  {
    "codigo": "MEDI",
    "nombre": "INTIBROXOL ADULTO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "INTIBROXOL AMP"
  },
  {
    "codigo": "MEDI",
    "nombre": "INTIBROXOL INFANTIL"
  },
  {
    "codigo": "MEDB21",
    "nombre": "INTRAFIX PRIMELINE AIR FS"
  },
  {
    "codigo": "MEDIIN-B34",
    "nombre": "INTROCAN Nº 18"
  },
  {
    "codigo": "MEDIIN-B35",
    "nombre": "INTROCAN Nº 20"
  },
  {
    "codigo": "INSU98",
    "nombre": "INTUBATING STYLET"
  },
  {
    "codigo": "MEDFS2",
    "nombre": "IPSON"
  },
  {
    "codigo": "MEDH",
    "nombre": "IVERMECTINA"
  },
  {
    "codigo": "INSU53",
    "nombre": "JERINGA 1 ML"
  },
  {
    "codigo": "EQUI7826",
    "nombre": "JERINGA 10 ML"
  },
  {
    "codigo": "INSU57",
    "nombre": "JERINGA 20 ML"
  },
  {
    "codigo": "INSU54",
    "nombre": "JERINGA 3ML"
  },
  {
    "codigo": "INSU55",
    "nombre": "JERINGA 5 ML"
  },
  {
    "codigo": "INSU58",
    "nombre": "JERINGA 50 ML"
  },
  {
    "codigo": "MEDICR3",
    "nombre": "KETAMIN"
  },
  {
    "codigo": "MEDBP",
    "nombre": "KETOFLEX DUO"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "KETOPROFENO 100"
  },
  {
    "codigo": "MEDFS3",
    "nombre": "KEVAL"
  },
  {
    "codigo": "MEDIN",
    "nombre": "KIN GINGIVAL COMPLEX"
  },
  {
    "codigo": "MEDBP",
    "nombre": "L DEXAMINO"
  },
  {
    "codigo": "MEDF",
    "nombre": "LABEBLOCK"
  },
  {
    "codigo": "MEDI7778",
    "nombre": "LAGRIMAS ARTIFICIALES"
  },
  {
    "codigo": "MEDI6691",
    "nombre": "LANZOPRAL AMPOLLA"
  },
  {
    "codigo": "INSU116",
    "nombre": "LAPIZ DE ELECTROBISTURI"
  },
  {
    "codigo": "MEDIN",
    "nombre": "LAXUAVE"
  },
  {
    "codigo": "MEDTC3",
    "nombre": "LERTUS GEL"
  },
  {
    "codigo": "MED",
    "nombre": "LEUKOMED T"
  },
  {
    "codigo": "AYV",
    "nombre": "LEVOALCOAS IV"
  },
  {
    "codigo": "MEDIC3",
    "nombre": "LEVOFLOXACINA 750"
  },
  {
    "codigo": "MEDIPM4",
    "nombre": "LIBBERA"
  },
  {
    "codigo": "AYV136",
    "nombre": "LIDOCAINA 1%"
  },
  {
    "codigo": "AYV",
    "nombre": "LIDOCAINA 2% 10"
  },
  {
    "codigo": "MEDIF",
    "nombre": "LIDOCAINA 2% 20 ML"
  },
  {
    "codigo": "AYV",
    "nombre": "LIDOCAINA 2% 5ML"
  },
  {
    "codigo": "MEDIN",
    "nombre": "LIDRAMINA"
  },
  {
    "codigo": "MEDIIN-B27",
    "nombre": "LINOVERA"
  },
  {
    "codigo": "MEDI7795",
    "nombre": "LIPOFUNDIN 10% FCO 500ML"
  },
  {
    "codigo": "INSU59",
    "nombre": "LLAVE DE 3 VIAS"
  },
  {
    "codigo": "INSU61",
    "nombre": "LLAVE DE 3 VIAS CON ALARGADOR 50 CM"
  },
  {
    "codigo": "MEDIC2",
    "nombre": "LOSARTAN"
  },
  {
    "codigo": "MEDIPM3",
    "nombre": "MAGAL D"
  },
  {
    "codigo": "MEDI7782",
    "nombre": "MAGNESIO VIMIN"
  },
  {
    "codigo": "MEDIIN-B14",
    "nombre": "MANITOL 500 ML"
  },
  {
    "codigo": "MEDB",
    "nombre": "MAXIBIOTIC 1000"
  },
  {
    "codigo": "MEDIBPH26",
    "nombre": "MAXIBONAGEL"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MEBIDOX 400"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MEBIDOX 600"
  },
  {
    "codigo": "MEDI6692",
    "nombre": "MEGATADINA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "MELOXICAM"
  },
  {
    "codigo": "MEDI7827",
    "nombre": "MELOXICAM 15MG"
  },
  {
    "codigo": "MEDA",
    "nombre": "MEROPENEM 1 GR"
  },
  {
    "codigo": "MEDLQ4",
    "nombre": "METIL PREDGAL"
  },
  {
    "codigo": "MEDIF",
    "nombre": "METOCLOPRAMIDA"
  },
  {
    "codigo": "MEDI6761",
    "nombre": "METOCLOPRAMIDA 10MG AMPOLLA"
  },
  {
    "codigo": "MEDI6763",
    "nombre": "METOCLOPRAMIDA 10MG MUNDO PHARMA"
  },
  {
    "codigo": "MEDIPM1",
    "nombre": "METROCAPS"
  },
  {
    "codigo": "AYV42",
    "nombre": "METROGYN"
  },
  {
    "codigo": "MEDIIN-B18",
    "nombre": "METRONAC"
  },
  {
    "codigo": "INSU62",
    "nombre": "MICROGOTERO 150 ML"
  },
  {
    "codigo": "INSU130",
    "nombre": "MICROPORE 3M 2.5"
  },
  {
    "codigo": "EQUI7772",
    "nombre": "MICROPORE 3M 5CMX9,1M"
  },
  {
    "codigo": "MED",
    "nombre": "MIDAZOLAM 15 MG"
  },
  {
    "codigo": "MED",
    "nombre": "MORFINA 10 MG"
  },
  {
    "codigo": "MEDIT2",
    "nombre": "MOXILIN 1G"
  },
  {
    "codigo": "MEDIT6",
    "nombre": "MOXILIN 500 MG"
  },
  {
    "codigo": "MEDINT15",
    "nombre": "MUXATIL"
  },
  {
    "codigo": "MEDIN",
    "nombre": "MUXATIL AMP"
  },
  {
    "codigo": "MEDFS4",
    "nombre": "MUXOL"
  },
  {
    "codigo": "MEDIN6",
    "nombre": "MYCOTIX 200"
  },
  {
    "codigo": "MEDA",
    "nombre": "NALOXONA"
  },
  {
    "codigo": "MEDM",
    "nombre": "NASOXY SPRAY"
  },
  {
    "codigo": "MEDI7816",
    "nombre": "NEOSTIGMINA"
  },
  {
    "codigo": "MEDBP",
    "nombre": "NEOTREX"
  },
  {
    "codigo": "MEDFS11",
    "nombre": "NERBEDOL B 12 10000"
  },
  {
    "codigo": "MEDIN",
    "nombre": "NEUROTRAT FORTE 10000"
  },
  {
    "codigo": "MEDFS9",
    "nombre": "NEUROVAL CD 10 MG"
  },
  {
    "codigo": "MEDIF",
    "nombre": "NISTATINA"
  },
  {
    "codigo": "MEDS8",
    "nombre": "NODOL 400"
  },
  {
    "codigo": "MEDIS8",
    "nombre": "NOOPIRAM"
  },
  {
    "codigo": "MEDIS4",
    "nombre": "NOOPIRAM"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NORADRENALINA"
  },
  {
    "codigo": "MEDIBPH14",
    "nombre": "NOVADOL"
  },
  {
    "codigo": "MEDIBPH13",
    "nombre": "NOVADOL 75"
  },
  {
    "codigo": "MEDIBPH12",
    "nombre": "NOVADOL FORTE"
  },
  {
    "codigo": "POM11",
    "nombre": "NOVADOL GEL"
  },
  {
    "codigo": "MEDN2",
    "nombre": "NOVO PENCIL 12.6.6"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NOVONOX 40"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NOVONOX 60"
  },
  {
    "codigo": "MEDNV",
    "nombre": "NOVOPENCIL FORTE 12.6.6"
  },
  {
    "codigo": "MEDIN",
    "nombre": "OMEGASTRIN 20"
  },
  {
    "codigo": "MEDIN",
    "nombre": "OMEGASTRIN 40"
  },
  {
    "codigo": "MEDI7828",
    "nombre": "OMEPRAZOL 20MG"
  },
  {
    "codigo": "AYV",
    "nombre": "OMEPRAZOL 40 MG"
  },
  {
    "codigo": "MEDNV",
    "nombre": "ONDANSETRON"
  },
  {
    "codigo": "MEDQ3",
    "nombre": "OSELTA 75"
  },
  {
    "codigo": "MEDB",
    "nombre": "OXAR D"
  },
  {
    "codigo": "MEDLG2",
    "nombre": "OXITOCINA"
  },
  {
    "codigo": "MED",
    "nombre": "PACO"
  },
  {
    "codigo": "MED",
    "nombre": "PAPEL DE ELECTROCARDIOGR AMA"
  },
  {
    "codigo": "MEDIF",
    "nombre": "PARACETAMOL 100 MG"
  },
  {
    "codigo": "MEDH",
    "nombre": "PARACETAMOL 125 MG"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "PARACETAMOL 1GR"
  },
  {
    "codigo": "MEDF",
    "nombre": "PAUSE"
  },
  {
    "codigo": "MEDB",
    "nombre": "PEN DI BEN 2.400.000 U.I"
  },
  {
    "codigo": "MED",
    "nombre": "PENCAN Nº 27"
  },
  {
    "codigo": "MEDIN",
    "nombre": "PENTRAX AC"
  },
  {
    "codigo": "INSB5",
    "nombre": "PERICAN 18GX1/4"
  },
  {
    "codigo": "MEDF",
    "nombre": "PIPEBAC T"
  },
  {
    "codigo": "MEDI7821",
    "nombre": "PIPERACICLINA MAS TAZOBACTAM 4,5"
  },
  {
    "codigo": "MEDT",
    "nombre": "PIREDOL 100"
  },
  {
    "codigo": "MEDIB30",
    "nombre": "PIRONAL FLU"
  },
  {
    "codigo": "MEDIB32",
    "nombre": "PIRONAL FORTE"
  },
  {
    "codigo": "MEDBP",
    "nombre": "PLATELET"
  },
  {
    "codigo": "INSU63",
    "nombre": "PORTAOBJETO"
  },
  {
    "codigo": "MEDC",
    "nombre": "POTASIO CL 1.3 MEQ"
  },
  {
    "codigo": "MEDQ5",
    "nombre": "PREBALIN 75"
  },
  {
    "codigo": "MEDISLCH5",
    "nombre": "PREDNISONA LCH"
  },
  {
    "codigo": "MEDS10",
    "nombre": "PREDNISONA LCH 5"
  },
  {
    "codigo": "MEDC",
    "nombre": "PREGABALINA"
  },
  {
    "codigo": "MED",
    "nombre": "PRESERVATIVO"
  },
  {
    "codigo": "MEDI7820",
    "nombre": "PRESTAT 75"
  },
  {
    "codigo": "MEDBP",
    "nombre": "PROCIN DIGEST"
  },
  {
    "codigo": "MEDIIN-B26",
    "nombre": "PROPOFOL LIPURO"
  },
  {
    "codigo": "POM3",
    "nombre": "QUEMACURAN L"
  },
  {
    "codigo": "MEDI7793",
    "nombre": "QUETIAPINA 100MG"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "QUETOROL 30 AMP"
  },
  {
    "codigo": "MEDIN",
    "nombre": "QUETOROL 30 SL"
  },
  {
    "codigo": "MEDIN",
    "nombre": "QUETOROL 30MG"
  },
  {
    "codigo": "MEDI7817",
    "nombre": "QUETOROL TRAM"
  },
  {
    "codigo": "MEDB",
    "nombre": "REMITEX"
  },
  {
    "codigo": "POM24",
    "nombre": "RIFAMICINA 10MG/ML"
  },
  {
    "codigo": "MEDM",
    "nombre": "RIXAM 1000"
  },
  {
    "codigo": "MEDM",
    "nombre": "RIXAM 250"
  },
  {
    "codigo": "MEDM13",
    "nombre": "RIXAM 250"
  },
  {
    "codigo": "MEDI7780",
    "nombre": "RIXAM 500"
  },
  {
    "codigo": "POM13",
    "nombre": "ROXICAINA JALEA"
  },
  {
    "codigo": "MEDS4",
    "nombre": "SALBUTAMOL AEROSOL"
  },
  {
    "codigo": "MEDB",
    "nombre": "SEPTICIDE 500"
  },
  {
    "codigo": "MEDF",
    "nombre": "SEVOFLURANO"
  },
  {
    "codigo": "MEDINT28",
    "nombre": "SIDEAL FORTE INT"
  },
  {
    "codigo": "MEDINT26",
    "nombre": "SIDERAL FOLIC"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SIDERAL ORO"
  },
  {
    "codigo": "MEDBP",
    "nombre": "SIGNUM M"
  },
  {
    "codigo": "MEDIS1",
    "nombre": "SINALERG 4"
  },
  {
    "codigo": "MEDF",
    "nombre": "SITEX 100"
  },
  {
    "codigo": "MEDF",
    "nombre": "SITEX FORTE"
  },
  {
    "codigo": "MED",
    "nombre": "SOL FISIOLOGICA 1000ML"
  },
  {
    "codigo": "MED",
    "nombre": "SOL FISIOLOGICA 100ML"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL GLUCOSA 10% 1000ML"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL GLUCOSA 10% 500 ML"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL GLUCOSA 5% 1000 ML"
  },
  {
    "codigo": "MEDF",
    "nombre": "SOL GLUCOSA 50% 500 ML"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL IRRIGACION 1000 ML"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL RINGER LACTATO 1000ML"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL RINGER LACTATO 500 ML"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SOL RINGER NORMAL 1000"
  },
  {
    "codigo": "MEDI",
    "nombre": "SOL RINGER NORMAL 500"
  },
  {
    "codigo": "MEDIIN-B2",
    "nombre": "SOLUCION FISIOLOGICA 500 ML"
  },
  {
    "codigo": "MEDFS10",
    "nombre": "SOMNO XR"
  },
  {
    "codigo": "INSU70",
    "nombre": "SONDA FOLEY Nº 10 LATEX"
  },
  {
    "codigo": "INSU71",
    "nombre": "SONDA FOLEY Nº 12 LATEX"
  },
  {
    "codigo": "INSU72",
    "nombre": "SONDA FOLEY Nº 14"
  },
  {
    "codigo": "INSU73",
    "nombre": "SONDA FOLEY Nº 16 LATEX"
  },
  {
    "codigo": "INSU74",
    "nombre": "SONDA FOLEY Nº 18 LATEX"
  },
  {
    "codigo": "INSU79",
    "nombre": "SONDA NASOGASTRICA Nº 10"
  },
  {
    "codigo": "INSU80",
    "nombre": "SONDA NASOGASTRICA Nº 14"
  },
  {
    "codigo": "INSU82",
    "nombre": "SONDA NASOGASTRICA Nº 18"
  },
  {
    "codigo": "INSU75",
    "nombre": "SONDA NASOGASTRICA Nº 4"
  },
  {
    "codigo": "INSU76",
    "nombre": "SONDA NASOGASTRICA Nº 6 O K33"
  },
  {
    "codigo": "INSU78",
    "nombre": "SONDA NASOGASTRICA Nº10"
  },
  {
    "codigo": "INSU77",
    "nombre": "SONDA NASOGASTRICA Nº8"
  },
  {
    "codigo": "INSU81",
    "nombre": "SONDA NASOGATRICA Nº 16"
  },
  {
    "codigo": "INSU122",
    "nombre": "SONDA NELATON Nº 16"
  },
  {
    "codigo": "MEDIIN-B39",
    "nombre": "SPINOCAN Nº 22"
  },
  {
    "codigo": "MEDIIN-B40",
    "nombre": "SPINOCAN Nº 25"
  },
  {
    "codigo": "MEDIIN-B41",
    "nombre": "SPINOCAN Nº 26"
  },
  {
    "codigo": "MEDB14",
    "nombre": "STOPER"
  },
  {
    "codigo": "INSU173",
    "nombre": "STYPCEL"
  },
  {
    "codigo": "MEDBP",
    "nombre": "SUCRABONAGEL"
  },
  {
    "codigo": "MEDM",
    "nombre": "SUCRALTIP"
  },
  {
    "codigo": "MEDA",
    "nombre": "SUERO DE LA VIDA"
  },
  {
    "codigo": "AYV49",
    "nombre": "SULFATO DE MAGNESIO"
  },
  {
    "codigo": "MEDIN",
    "nombre": "SUMAX 50"
  },
  {
    "codigo": "MEDTC5",
    "nombre": "SUPRACAM"
  },
  {
    "codigo": "MEDTC6",
    "nombre": "SUPRACAM 15 AMP"
  },
  {
    "codigo": "MEDTC4",
    "nombre": "SUPRACAM FLEX"
  },
  {
    "codigo": "POM4",
    "nombre": "SUPRACORTIN"
  },
  {
    "codigo": "MEDB",
    "nombre": "T4"
  },
  {
    "codigo": "MEDIB39",
    "nombre": "TALFLEX"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX 100"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX B1 B6 B12"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX B1B6B12 AMP"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX B1B6B12 FORTE"
  },
  {
    "codigo": "MEDB",
    "nombre": "TALFLEX BI"
  },
  {
    "codigo": "MEDIF",
    "nombre": "TAMBOL"
  },
  {
    "codigo": "MEDF",
    "nombre": "TAMBOL COMP"
  },
  {
    "codigo": "MEDF",
    "nombre": "TAMBOL FORTE"
  },
  {
    "codigo": "MEDIPM5",
    "nombre": "TAZAROL"
  },
  {
    "codigo": "INSU127",
    "nombre": "TEGADERM"
  },
  {
    "codigo": "MEDIF",
    "nombre": "TENSIUM 20"
  },
  {
    "codigo": "MEDIF",
    "nombre": "TENSIUM 40"
  },
  {
    "codigo": "MEDAYV",
    "nombre": "TERBOCAINA 2%"
  },
  {
    "codigo": "MEDI7810",
    "nombre": "TERBOCAINA AMPOLLA INYECTABLE"
  },
  {
    "codigo": "MEDIT4",
    "nombre": "TERBOCYL 6.3.3"
  },
  {
    "codigo": "MEDIT5",
    "nombre": "TERBOCYL FORTE"
  },
  {
    "codigo": "AYV21",
    "nombre": "TERBOMICINA"
  },
  {
    "codigo": "AYV",
    "nombre": "TERMETIK"
  },
  {
    "codigo": "MED",
    "nombre": "TERMOMETRO DE MERCURIO"
  },
  {
    "codigo": "INSU83",
    "nombre": "TESTIGO HUMEDO"
  },
  {
    "codigo": "INSU84",
    "nombre": "TESTIGO SECO"
  },
  {
    "codigo": "MEDLF5",
    "nombre": "TIAMIGAL"
  },
  {
    "codigo": "MEDIB1A",
    "nombre": "TIAXAL I M"
  },
  {
    "codigo": "MEDIB6",
    "nombre": "TIAXAL IV"
  },
  {
    "codigo": "MEDIPM9",
    "nombre": "TOBRAZOL"
  },
  {
    "codigo": "MEDI7790",
    "nombre": "TOCEX JARABE"
  },
  {
    "codigo": "POM22",
    "nombre": "TOPICREM"
  },
  {
    "codigo": "MEDIN",
    "nombre": "TORNIX 20"
  },
  {
    "codigo": "MEDB7",
    "nombre": "TRACUTIL"
  },
  {
    "codigo": "MEDI7801",
    "nombre": "TRANEST ACIDO TRANEXAMICO 500MG/5ML"
  },
  {
    "codigo": "MEDB16",
    "nombre": "TRANSOFIX N"
  },
  {
    "codigo": "MEDB",
    "nombre": "TRIAPEN FORTE"
  },
  {
    "codigo": "MEDIN",
    "nombre": "TROXOLINA 500"
  },
  {
    "codigo": "MEDIN",
    "nombre": "TROXOLINA CAP"
  },
  {
    "codigo": "INSU85",
    "nombre": "TUBO ARMADO N 6.5"
  },
  {
    "codigo": "INSU86",
    "nombre": "TUBO ARMADO Nº 7"
  },
  {
    "codigo": "MED",
    "nombre": "TUBO DE TRAQUEOSTOMIA 7"
  },
  {
    "codigo": "MED",
    "nombre": "TUBO DE TRAQUEOSTOMIA 7.5"
  },
  {
    "codigo": "EQUI6767",
    "nombre": "TUBO ENDOTRAQUEAL Nª 7.0 CON BALON"
  },
  {
    "codigo": "EQUI6769",
    "nombre": "TUBO ENDOTRAQUEAL Nª7.5 CON BALON"
  },
  {
    "codigo": "MED",
    "nombre": "TUBO ENDOTRAQUEAL Nº 2.5"
  },
  {
    "codigo": "INSU89",
    "nombre": "TUBO ENDOTRAQUEAL Nº 3"
  },
  {
    "codigo": "INSU90",
    "nombre": "TUBO ENDOTRAQUEAL Nº 4"
  },
  {
    "codigo": "INSU92",
    "nombre": "TUBO ENDOTRAQUEAL Nº 5"
  },
  {
    "codigo": "INSU93",
    "nombre": "TUBO ENDOTRAQUEAL Nº 5.5"
  },
  {
    "codigo": "INSU94",
    "nombre": "TUBO ENDOTRAQUEAL Nº 6"
  },
  {
    "codigo": "INSU95",
    "nombre": "TUBO ENDOTRAQUEAL Nº 6,5"
  },
  {
    "codigo": "INSU97",
    "nombre": "TUBO ENDOTRAQUEAL Nº 8"
  },
  {
    "codigo": "MEDI7791",
    "nombre": "TUSABRON"
  },
  {
    "codigo": "MEDIB35",
    "nombre": "TUSIGEN"
  },
  {
    "codigo": "MEDIB34",
    "nombre": "TUSIGEN INFANTIL"
  },
  {
    "codigo": "MEDM",
    "nombre": "TUSILEXIL D"
  },
  {
    "codigo": "MEDI11",
    "nombre": "TUSSINOL"
  },
  {
    "codigo": "MEDC3",
    "nombre": "ULTRAVIST 300"
  },
  {
    "codigo": "MEDI7794",
    "nombre": "ULTRAVIST 300/50ML"
  },
  {
    "codigo": "MEDI7824",
    "nombre": "UROGRAFINA 76%"
  },
  {
    "codigo": "MEDH",
    "nombre": "UROHAN"
  },
  {
    "codigo": "MEDI7802",
    "nombre": "UVAMIN RETARD 100MG"
  },
  {
    "codigo": "MEDI7797",
    "nombre": "VALAX 160MG"
  },
  {
    "codigo": "MEDI7798",
    "nombre": "VALAXAM D"
  },
  {
    "codigo": "MEDA",
    "nombre": "VANCOMICINA 1GR"
  },
  {
    "codigo": "MEDIF",
    "nombre": "VANCOMICINA 500"
  },
  {
    "codigo": "MEDLQ14",
    "nombre": "VARDACTONE 25"
  },
  {
    "codigo": "INSU111",
    "nombre": "VENDA COBAN 3\""
  },
  {
    "codigo": "INSU104",
    "nombre": "VENDA DE GASA 10 CM"
  },
  {
    "codigo": "INSU105",
    "nombre": "VENDA DE GASA 15 CM"
  },
  {
    "codigo": "MED",
    "nombre": "VENDA DE GASA 20 CM"
  },
  {
    "codigo": "INSU102",
    "nombre": "VENDA DE GASA 5 CM"
  },
  {
    "codigo": "INSU103",
    "nombre": "VENDA DE GASA 7,5 CM"
  },
  {
    "codigo": "INSU112",
    "nombre": "VENDA DE YESO 10 CM"
  },
  {
    "codigo": "INSU113",
    "nombre": "VENDA DE YESO 15 CM"
  },
  {
    "codigo": "INSU114",
    "nombre": "VENDA DE YESO 20 CM"
  },
  {
    "codigo": "INSU108",
    "nombre": "VENDA ELASTICA 10 CM"
  },
  {
    "codigo": "INSU109",
    "nombre": "VENDA ELASTICA 15 CM"
  },
  {
    "codigo": "INSU110",
    "nombre": "VENDA ELASTICA 20 CM"
  },
  {
    "codigo": "INSU107",
    "nombre": "VENDA ELASTICA 5 CM"
  },
  {
    "codigo": "INSU6759",
    "nombre": "VENDAS DE GASA 10CM"
  },
  {
    "codigo": "MEDM7",
    "nombre": "VIADIL COMPUESTO"
  },
  {
    "codigo": "MEDIM6",
    "nombre": "VIADIL COMPUESTO NF"
  },
  {
    "codigo": "MEDIN",
    "nombre": "VIRUSAN 500"
  },
  {
    "codigo": "AYV51",
    "nombre": "VITAMINA C"
  },
  {
    "codigo": "MEDT",
    "nombre": "VITAMINA C 2 SOBRE"
  },
  {
    "codigo": "AYV",
    "nombre": "VITAMINA K"
  },
  {
    "codigo": "MEDI6764",
    "nombre": "VITAMINA K MUNDO PHARMA"
  },
  {
    "codigo": "MEDF",
    "nombre": "XAMIC"
  },
  {
    "codigo": "MEDI",
    "nombre": "ZINC VIMIN"
  },
  {
    "codigo": "MEDINT20",
    "nombre": "ZOLION RELAX"
  }
]
JSON, true);

        foreach ($productos as $producto) {
            DB::table('productos')
                ->where('codigo', $producto['codigo'])
                ->where('nombre', $producto['nombre'])
                ->delete();
        }
    }
};
