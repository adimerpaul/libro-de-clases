# Módulo Curricular 3.0 — Modelo de datos propuesto

## Objetivo
Convertir Currículum Nacional en un **catálogo curricular versionado y relacionable**, para que Planificador, Evaluaciones, Biblioteca, PIE/DUA, UTP y Libro de Clases consuman la misma fuente.

La cadena operativa es:

`Nivel → Curso → Asignatura/Ámbito → Eje/Núcleo → Unidad → OA → Indicadores/Habilidades → Recursos → Planificación → Evaluación → Evidencia`

## Regla arquitectónica principal
Los registros oficiales y los registros creados por la plataforma no se mezclan.

- `learning_objectives.official_record = 1`: contenido curricular de fuente oficial.
- `indicators.source_kind = OFFICIAL`: indicador importado de fuente oficial.
- `indicators.source_kind = PLATFORM_SUGGESTED`: sugerencia pedagógica creada por la plataforma.
- `assessment_items.source_kind = PLATFORM_GENERATED | TEACHER | OFFICIAL_LINKED`: evita presentar preguntas creadas por la plataforma como banco oficial MINEDUC.
- `source_registry`: conserva URL, editor, fecha de verificación y licencia.

## Tablas principales

### Catálogo oficial
- `source_registry`
- `curriculum_versions`
- `education_levels`
- `grades`
- `subjects`
- `grade_subjects`
- `curriculum_axes`
- `curriculum_units`
- `learning_objectives`
- `unit_objectives`
- `indicators`
- `skills_competencies`
- `objective_skills`

### Recursos pedagógicos
- `resource_catalog`
- `resource_objectives`

### Planificación
- `planning_templates`
- `teacher_plans`
- `teacher_plan_objectives`
- `teacher_plan_resources`

### Evaluación y corrección
- `assessment_items`
- `assessment_item_objectives`
- `assessment_instruments`
- `assessment_instrument_objectives`
- `assessment_instrument_items`
- `assessment_results`

### Educación Media TP
- `tp_specialties`
- `tp_modules`
- `tp_module_objectives`

### Gobernanza de importaciones
- `import_runs`

## Soporte de niveles
La estructura está preparada para:
- Educación Parvularia: SC, NM, NT.
- 1° a 6° Básico.
- 7° Básico a 2° Medio.
- 3° y 4° Medio Formación General / Humanista-Científico.
- 3° y 4° Medio Técnico-Profesional.
- EPJA como expansión posterior.

## Flujo del Planificador
1. Usuario selecciona nivel/curso.
2. API devuelve las asignaturas válidas para ese curso.
3. Usuario selecciona asignatura.
4. API devuelve ejes/unidades/OA vigentes.
5. Usuario selecciona uno o más OA.
6. `GET /oa/{id}/bundle` devuelve:
   - texto del OA;
   - eje o núcleo;
   - unidad;
   - habilidades/competencias;
   - indicadores oficiales o sugeridos claramente etiquetados;
   - recursos relacionados;
   - fuentes y licencias.
7. El Planificador crea un borrador con inicio, desarrollo, cierre, recursos, evaluación, DUA y organización tiempo/espacio.
8. Al guardar se crea `teacher_plans` + `teacher_plan_objectives`.

## Flujo del Creador/Corrector de Evaluaciones
1. Usa el mismo selector Curso → Asignatura → OA.
2. El docente elige propósito, tipo de instrumento, cantidad de ítems, dificultad, profundidad y exigencia.
3. El generador consulta `assessment_items` relacionados al OA o crea nuevos ítems de plataforma.
4. El instrumento guarda la relación OA ↔ ítem ↔ puntaje.
5. La corrección queda dentro del mismo dominio mediante `assessment_results`.
6. UTP puede revisar alineación: OA planificado → OA evaluado → resultados.

## Política de recursos y licencias
No copiar automáticamente archivos del portal oficial a una plataforma comercial.

`resource_catalog.hosting_policy` debe usar, como mínimo:
- `LINK_ONLY`: mostrar ficha, metadatos y enlace a fuente original.
- `EMBED_ALLOWED`: solo si la licencia y condiciones permiten incrustación.
- `LOCAL_COPY_ALLOWED`: solo con licencia o autorización que permita copia/uso previsto.

Los flags `commercial_use_allowed` y `derivative_use_allowed` permiten bloquear acciones de interfaz.

## Versionado
Nunca modificar un OA oficial existente para “actualizarlo”. Si cambia el currículo:
1. Crear nueva `curriculum_version`.
2. Cargar nueva oferta `grade_subjects`.
3. Cargar nuevos OA.
4. Mantener los OA anteriores para reconstruir planificaciones históricas.

## Integración con los módulos existentes
- **Libro de Clases:** muestra OA planificado y OA trabajado por clase.
- **Planificador:** consume catálogo y guarda planificación.
- **Evaluaciones:** usa OA e indicadores como restricciones de generación.
- **Biblioteca:** busca recursos por OA, curso, asignatura, tipo y licencia.
- **UTP:** mide cobertura por OA y unidad.
- **PIE/DUA:** agrega adecuaciones sin modificar el OA oficial.
- **Sostenedor:** analiza cobertura agregada, nunca altera contenido curricular.
