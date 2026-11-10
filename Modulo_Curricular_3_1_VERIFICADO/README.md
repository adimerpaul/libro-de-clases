# Módulo Curricular 3.0 — Base de datos integrable

Este paquete contiene una solución concreta para transformar Currículum Nacional en un servicio curricular para Libro de Clases 3.0.

## Incluye
- `database/curriculum_core_demo.sqlite`: base SQLite funcional, ya poblada con estructura completa y una semilla verificable de 1° Básico · Artes Visuales.
- `database/build_sqlite.py`: reconstruye la base desde cero.
- `database/CONSULTAS_DEMO.sql`: consultas para probar selector, OA, recursos, planificación y evaluación.
- `docs/MODELO_DATOS.md`: arquitectura y reglas de versionado/licencias.
- `docs/INTEGRACION_LARAVEL.md`: integración recomendada para backend Laravel/API.
- `api/openapi.yaml`: contrato de endpoints.
- `importer/import_contract.example.json`: contrato para cargas curriculares posteriores.

## Prueba rápida

```bash
python3 database/build_sqlite.py
sqlite3 database/curriculum_core_demo.sqlite
```

Dentro de SQLite:

```sql
.headers on
.mode column
SELECT * FROM v_curriculum_selector;
SELECT oa_code, official_text, resource_count FROM v_oa_bundle;
```

## Qué contiene la semilla
- Estructura de niveles desde Parvularia a Media TP.
- 1° Básico + Artes Visuales como oferta curricular demostrativa.
- 5 OA de referencia, relacionados con ejes.
- Unidad 1 y metadatos parciales de Unidad 2.
- Recursos oficiales enlazados por OA.
- Una planificación de demostración.
- Un instrumento de evaluación creado por la plataforma y unido al mismo OA.

## Importante sobre licencias
El diseño guarda licencia y política de alojamiento por cada recurso. Los archivos con restricciones `NC` (no comercial) se dejan como `LINK_ONLY` por defecto. Esto es deliberado: una plataforma comercial debe enlazar o solicitar autorización cuando corresponda, en vez de copiar automáticamente recursos con restricciones.

## Próximo paso de producción
1. Llevar estas tablas a la BD actual de la plataforma.
2. Crear `CurriculumService` y los endpoints del OpenAPI.
3. Conectar el selector del Planificador y de Evaluaciones al mismo servicio.
4. Construir un proceso de importación oficial y revisión humana por versiones.
5. Completar el catálogo por nivel/asignatura de forma incremental y auditable.
