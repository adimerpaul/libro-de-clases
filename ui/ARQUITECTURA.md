# Arquitectura funcional — Ecosistema Educativo 4.2

## Flujo pedagógico principal
`Nivel/Curso → Asignatura/Núcleo → OA → Planificación → Actividad/Recurso → Instrumento de evaluación → Corrección → Libro → Student 360 / Familia / UTP`

La clave de diseño es reutilizar el dato: el OA seleccionado no se vuelve a escribir manualmente en cada módulo.

## Módulo Planificador
El contexto curricular debe persistir como entidades vinculadas:
- level_id
- subject_id
- oa_id
- official_source_version
- contents
- competencies
- indicators
- assessment_evidence
- teaching_strategy
- phases: start/development/closure
- resources
- time_space
- dua_accommodations

## Módulo Evaluación
Instrument debe conservar:
- level_id
- subject_id
- oa_id
- type
- item_count
- difficulty
- purpose
- weight
- threshold
- criteria
- adapted_version
- creator / validator / timestamps

Correction debe vincularse a instrument_id y student_id, nunca a un instrumento genérico separado.

## Libreta del Estudiante
Tres dominios separados pero conectados:
1. Identity & Institution.
2. Family-School Communication.
3. Student Planning & Learning Evidence.

En producción, identificación, incidentes, firmas y datos familiares deben tener permisos reforzados, finalidad explícita y auditoría de acceso.
