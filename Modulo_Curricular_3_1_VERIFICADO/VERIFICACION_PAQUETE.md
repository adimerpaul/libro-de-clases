# Verificación del paquete — Módulo Curricular 3.1

Este paquete fue reconstruido y verificado después de detectar que la versión anterior contenía un script con una ruta absoluta de trabajo.

## Corrección aplicada
`database/build_sqlite.py` ahora genera la base de datos en la misma carpeta del script, sin depender de `/mnt/data` ni de la máquina donde fue creado.

## Pruebas ejecutadas
- Integridad ZIP: OK.
- Extracción completa: OK.
- Compilación Python de `build_sqlite.py`: OK.
- Reconstrucción de SQLite desde cero en una carpeta temporal: OK.
- Apertura de SQLite y lectura de tablas/vistas: OK.
- Consultas `v_curriculum_selector` y `v_oa_bundle`: OK.

## Archivos principales
- `database/curriculum_core_demo.sqlite` — base SQLite ya poblada.
- `database/build_sqlite.py` — reconstruye la base desde cero.
- `database/CONSULTAS_DEMO.sql` — consultas de prueba.
- `database/laravel/...migration.php` — migración de referencia para Laravel.
- `database/laravel/CurriculumDemoSeeder.php` — seeder de referencia.
- `docs/MODELO_DATOS.md` — modelo de datos.
- `docs/INTEGRACION_LARAVEL.md` — guía de integración.
- `api/openapi.yaml` — contrato API.
- `importer/import_contract.example.json` — contrato de importación.
- `MANIFEST_SHA256.txt` — hashes y tamaños para verificar recepción.

## Contenido actual de la base de demostración
La semilla actual valida el modelo técnico, no pretende ser todavía el catálogo Mineduc completo.
Incluye 6 niveles educativos, 19 grados/niveles, 9 asignaturas maestras, 5 OA de Artes Visuales de 1° Básico, 3 recursos, 1 planificación y 1 instrumento de evaluación.
