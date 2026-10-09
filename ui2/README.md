# Ecosistema Educativo 3.0 — DEMO funcional

Esta versión lleva el **Libro de Clases 3.0** a una arquitectura de ecosistema escolar. El libro físico-digital sigue siendo el núcleo, pero ahora comparte contexto con planificación, currículum/OA, evaluación, corrección, recursos, asistencia RFID, retiros, Student 360, comunidad y Control Tower.

## Acceso DEMO

- Correo: cualquiera
- Clave: `demo123`
- Perfiles: Docente, UTP, Dirección, Inspectoría, Sostenedor / DAEM / SLEP
- Todos los datos son ficticios.

## Cómo probar localmente

Desde esta carpeta:

```bash
python3 -m http.server 8080
```

Luego abrir:

`http://localhost:8080`

No abrir con `file://` si quieres probar el Service Worker / PWA.

## Módulos incluidos

1. **Inicio / Ecosistema**
   - Vista modular del producto.
   - Tareas que requieren atención hoy.
   - Continuidad OA → planificación → recursos/evaluación → Libro 3.0.

2. **Libro de Clases 3.0**
   - Portada tipo libro físico.
   - Doble página, lomo y pestañas.
   - Identificación, nómina, asistencia, leccionario, evaluaciones y observaciones.
   - Datos provenientes de otros módulos.

3. **Planificador pedagógico**
   - Vista semanal.
   - Planificación vinculada a OA.
   - Inicio / desarrollo / cierre / evidencia.
   - Adecuaciones y apoyos UDL de demostración.

4. **Currículum y OA**
   - Navegación por nivel y asignatura.
   - OA de demostración con habilidades y unidad.
   - Botones para reutilizar el OA en Planificador, Evaluaciones o Recursos.
   - Arquitectura preparada para ingestión masiva del catálogo oficial.

5. **Creador de instrumentos**
   - Selección de OA, tipo, dificultad y número de preguntas.
   - Generación de instrumento DEMO.
   - Vista imprimible.
   - Envío directo al corrector.

6. **Corrector de instrumentos**
   - Selección múltiple exacta.
   - Corrección demostrativa de respuestas abiertas basada en criterios/keywords.
   - Puntaje, porcentaje y retroalimentación.
   - Historial de correcciones.

7. **Biblioteca pedagógica**
   - Actividades, guías, presentaciones, material didáctico, juegos y videos.
   - Filtro por OA/asignatura.
   - Recurso → Planificación.

8. **Presence Engine / RFID UHF**
   - Portal establecimiento + portal sala.
   - 30 estudiantes ficticios.
   - Excepciones y validación humana.
   - Firma de asistencia.

9. **Registro de retiro de estudiantes**
   - Estudiante.
   - Persona que retira.
   - Relación.
   - Verificación/autorización.
   - Motivo.
   - Hora.
   - Firma.
   - Auditoría.

10. **Student 360**
    - Identificación.
    - Asistencia.
    - Evaluaciones.
    - Trayectoria.
    - Privacidad por perfil (concepto DEMO).

11. **Comunidad educativa**
    - Familias.
    - Docentes y asistentes.
    - PIE/apoyo.
    - Avisos y autorizaciones.

12. **Reportes / Control Tower**
    - Asistencia por establecimiento.
    - Incidencias prioritarias.
    - Cumplimiento.
    - Auditoría.

13. **Presentación guiada de 10 minutos**
    - Botón en barra superior.
    - 10 pasos con narrativa de producto y cronómetro.

## Currículum Mineduc: alcance de esta DEMO

La interfaz y el modelo de datos están preparados para una biblioteca curricular completa, pero **esta versión de prueba no contiene la totalidad de los Objetivos de Aprendizaje oficiales**. Incluye ejemplos representativos para validar UX e integración entre módulos.

Para una versión productiva se recomienda construir un proceso ETL/importador que cargue y versione el catálogo oficial desde **Currículum Nacional (Mineduc)**, conservando al menos:

- nivel
- asignatura
- código OA
- texto oficial
- habilidades / actitudes
- indicadores de evaluación
- unidad/eje
- URL de fuente
- versión curricular
- vigencia

Fuentes oficiales de referencia usadas para estructurar el módulo:

- https://curriculumnacional.mineduc.cl/
- Matemática 2° medio: https://curriculumnacional.mineduc.cl/614/articles-34360_programa.pdf
- Lengua y Literatura 2° medio: https://curriculumnacional.mineduc.cl/614/articles-34446_programa.pdf

## Qué falta para producción real

- Backend seguro y multi-tenant.
- PostgreSQL / almacenamiento de objetos.
- Autenticación real, MFA y SSO.
- RBAC/ABAC server-side.
- Auditoría inmutable.
- Importador y versionado completo del currículum oficial.
- Integración EDE/SIGE real y pruebas por versión.
- Gateway RFID físico.
- Captura de firma con dispositivo.
- Corrección avanzada con IA, rúbricas, moderación y validación docente.
- Repositorio de recursos con licenciamiento y metadatos.
- Notificaciones reales (correo/SMS/push/WhatsApp según autorización y proveedor).
- Backups, observabilidad, hardening, pentest y cumplimiento jurídico/técnico.

## Propuesta de arquitectura productiva

- **Frontend:** React/Next.js o equivalente PWA.
- **API:** NestJS/FastAPI/.NET, arquitectura modular.
- **DB:** PostgreSQL.
- **Eventos:** cola/event bus para presencia, auditoría y notificaciones.
- **Objetos:** almacenamiento cifrado para instrumentos, PDFs y material.
- **Búsqueda:** índice curricular/recursos.
- **IA:** orquestador separado; nunca calcular notas oficiales en el LLM.
- **RFID:** gateway local + WebSocket/API con buffer offline.
- **Offline:** IndexedDB + cola idempotente.
- **Auditoría:** Event Store separado de logs técnicos.

## Importante

Esta DEMO sirve para validar producto, narrativa, UX y priorización. No debe almacenar datos reales de estudiantes ni utilizarse como libro oficial.
