# Ecosistema Educativo 4.2 — DEMO OA, Evaluación y Libreta

Esta versión profundiza la 4.1 en tres cambios visibles y funcionales:

1. **Planificador con selección en cascada Curso/Nivel → Asignatura/Núcleo → OA**.
2. **Creador y Corrector de Evaluaciones integrados en un mismo módulo**, con generación parametrizada según curso, asignatura, OA, tipo, cantidad de ítems, dificultad, propósito, ponderación, exigencia y adecuación.
3. **Libreta del Estudiante ampliada** como documento digital de identificación, comunicación familia–escuela y organización pedagógica/metacognitiva.

## Acceso
- Correo: cualquiera
- Clave: `demo123`
- Perfiles: Sostenedor, Docente, Educadora Diferencial, U.T.P., Inspectoría General, Convivencia Educativa y Apoderado/Tutor.

## Abrir en Mac
1. Descomprime el ZIP.
2. Haz doble clic en `INICIAR_DEMO_MAC.command`.
3. Si macOS bloquea la primera ejecución: clic derecho > Abrir.
4. Se abrirá `http://localhost:8092`.

## Abrir manualmente
```bash
python3 -m http.server 8092
```
Luego abrir `http://localhost:8092`.

## Cambios principales 4.2

### Planificador
- Selector de todos los niveles/trayectorias DEMO: Parvularia, Básica, Media, HC y TP.
- La asignatura cambia automáticamente según el nivel.
- El selector de OA muestra solo los OA asociados al nivel + asignatura elegidos.
- Al cambiar OA se actualizan contenidos, competencias/habilidades, indicadores, instrumentos, inicio/desarrollo/cierre, recursos y tiempo/espacio.
- En TP se muestra selector de especialidad y vínculo con desempeño técnico.

### Evaluaciones + Corrector integrado
El antiguo módulo `Corrector` deja de ser una herramienta separada. Dentro de `Evaluaciones` hay tres pestañas:
- Crear instrumento.
- Corregir instrumento.
- Historial.

Parámetros de generación:
- Curso/nivel.
- Asignatura/núcleo.
- OA.
- Tipo de instrumento.
- Cantidad de ítems.
- Dificultad.
- Propósito: diagnóstica, formativa o sumativa.
- Ponderación.
- Porcentaje de exigencia.
- Versión flexibilizada/adecuación evaluativa.

La vista previa cambia según el tipo elegido: prueba mixta, rúbrica, lista de cotejo, proyecto o desempeño TP.

### Libreta del Estudiante
La libreta ahora contiene tres grandes secciones:

#### 1. Información institucional e identificación
- Identificación del estudiante.
- Curso/grupo.
- Datos de contacto de emergencia.
- Datos del establecimiento.
- Jornada y canales oficiales.
- Reglamento interno abreviado: derechos, deberes, convivencia y seguridad.

#### 2. Comunicación y seguimiento
- Citaciones y entrevistas.
- Asistencia y justificaciones.
- Avisos de retiro anticipado.
- Incidentes y acuerdos.
- Firmas familia/docente/equipo directivo.
- Comunicación bidireccional establecimiento ↔ familia.
- Confirmación de lectura y trazabilidad.

#### 3. Organización y aprendizaje
- Calendario escolar y planificador de tareas/evaluaciones.
- Portafolio/bitácora de evidencias y reflexión metacognitiva.
- Criterios y rúbricas visibles para estudiante y familia.

## OA y fuentes oficiales
Los OA incluidos en esta DEMO son ejemplos estructurados para probar la interacción. No son una transcripción íntegra del catálogo oficial. La versión productiva debe importar, versionar y mantener la fuente oficial de Currículum Nacional/Mineduc.

## Privacidad
Todos los datos de la DEMO son ficticios. No utilizar datos personales reales en esta versión. Los permisos son demostrativos en frontend; producción requiere control server-side, auditoría, cifrado, autenticación real y segregación de dominios sensibles.
