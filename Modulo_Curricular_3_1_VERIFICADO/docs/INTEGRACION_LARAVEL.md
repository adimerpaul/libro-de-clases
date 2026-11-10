# Integración sugerida con Laravel

> La URL pública de la plataforma permite ver el login, pero sin credenciales no es posible confirmar desde fuera la versión exacta de Laravel ni el modelo de tablas actual. Por eso esta integración evita colisiones: el módulo curricular se puede montar con prefijo lógico `curriculum_*` o adaptar a los nombres existentes.

## Servicio recomendado
Crear `CurriculumService` con estas operaciones:

```php
levels();
grades($levelId);
subjects($gradeId);
units($gradeSubjectId);
objectives($gradeSubjectId, $unitId = null, $axisId = null);
objectiveBundle($objectiveId);
resources($objectiveId, array $filters = []);
```

## Rutas API

```php
Route::prefix('curriculum')->middleware('auth:sanctum')->group(function () {
    Route::get('/levels', [CurriculumController::class, 'levels']);
    Route::get('/grades', [CurriculumController::class, 'grades']);
    Route::get('/subjects', [CurriculumController::class, 'subjects']);
    Route::get('/units', [CurriculumController::class, 'units']);
    Route::get('/objectives', [CurriculumController::class, 'objectives']);
    Route::get('/objectives/{objective}/bundle', [CurriculumController::class, 'bundle']);
    Route::get('/resources', [CurriculumController::class, 'resources']);
});
```

## Ejemplo de selector en frontend

```js
// 1. curso
GET /api/curriculum/subjects?grade_id=4

// 2. asignatura
GET /api/curriculum/objectives?grade_subject_id=1

// 3. OA seleccionado
GET /api/curriculum/objectives/1/bundle
```

Respuesta esperada del bundle:

```json
{
  "id": 1,
  "code": "AR01-OA-01",
  "grade": "1° Básico",
  "subject": "Artes Visuales",
  "axis": "Expresar y crear visualmente",
  "official_text": "...",
  "indicators": [],
  "skills": [],
  "resources": [],
  "source": {
    "publisher": "MINEDUC / UCE",
    "url": "...",
    "license": null
  }
}
```

## Planificador
Guardar la selección curricular por ID, no copiando solamente texto libre:

```text
teacher_plans
  └── teacher_plan_objectives
       └── learning_objectives
```

Esto permite medir cobertura y saber qué versión curricular se utilizó.

## Evaluaciones
El instrumento debe nacer de `assessment_instrument_objectives`. Cada pregunta debe tener al menos un OA asociado mediante `assessment_item_objectives`.

Esto permite validación UTP automática:

```text
OA planificado = OA evaluado ? OK : revisar alineación
```

## Corrector
No crear un módulo desconectado. El corrector debe operar sobre `assessment_results` del mismo `assessment_instrument`. Así se conserva trazabilidad desde OA hasta resultado.

## Seguridad
- Docente: lectura del catálogo + CRUD de sus planificaciones/instrumentos.
- UTP: lectura global del catálogo + revisión de planificaciones/instrumentos.
- Sostenedor: métricas agregadas de cobertura.
- Administrador curricular: importación/versionado, sin permiso para alterar históricos.

## Caché
El catálogo cambia poco. Usar cache por selector:

```text
curriculum:subjects:{grade_id}
curriculum:oa:{grade_subject_id}:{version}
curriculum:bundle:{objective_id}
```

Invalidar únicamente al publicar una nueva versión curricular.
