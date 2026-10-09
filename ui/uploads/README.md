# Libro de Clases 3.0 — DEMO 2.0 FINAL

Esta versión transforma el MVP original en una interfaz cuyo **objeto central es un libro de clases físico digital**: portada, doble página, lomo, papel, pestañas, numeración y navegación tipo hojear. La capa tecnológica se incorpora alrededor y dentro del libro sin perder la lógica familiar del registro físico.

## Qué incluye
- Nombre de producto: **Libro de Clases 3.0 / Libro 3.0**.
- Logo conceptual original: libro abierto + señal digital/RFID.
- Establecimiento ficticio chileno: **Liceo Bicentenario Nueva Araucanía**.
- Curso ficticio: **2° Medio B · 2027**.
- 30 estudiantes ficticios y apoderados ficticios.
- Portada inspirada en la tipología del libro físico mostrado como referencia, sin copiar marcas de terceros.
- Doble página, lomo, textura de papel, pestañas laterales, numeración y efecto de giro de página.
- Navegación por flechas, teclado, pestañas y miniaturas inferiores.
- Identificación, índice, nómina, apoderados, asistencia mensual, leccionario, evaluaciones, observaciones y seguimiento.
- **Presence Engine / RFID UHF** con dos portales simulados, 30 alumnos animados, conteo en vivo y nombre/confianza de cada lectura.
- Separación entre evento RFID y asistencia oficial.
- Bandeja de excepciones con validación humana y motivo auditable.
- Firma/cierre de asistencia.
- Registro y firma del leccionario.
- Student 360 de demostración.
- Time Machine / auditoría y Control Tower para sostenedor.
- Exportaciones demo JSON.
- Presentación guiada integrada de **10 minutos**, con cronómetro y guion.
- PWA/offline básico y persistencia local mediante localStorage.

## Acceso
- Correo: cualquiera.
- Clave: `demo123`
- Perfiles: Docente, Dirección, UTP, Sostenedor / DAEM / SLEP.

## Probar localmente
Desde esta carpeta:

```bash
python3 -m http.server 8080
```

Luego abrir `http://localhost:8080`.

## Presentación sugerida
1. Abrir como Docente.
2. Pulsar **Presentación 10 min**.
3. Mostrar portada y hojear con flechas/miniaturas.
4. Ir a Asistencia y después a RFID.
5. Pulsar **Simular ingreso de 30 estudiantes**.
6. Resolver la excepción pendiente y firmar el bloque.
7. Registrar/firma una clase en Leccionario.
8. Terminar en Auditoría + Control Tower.

## Importante
Es una DEMO funcional para validar producto, UX y narrativa comercial. No es un libro oficial y no debe almacenar datos personales reales. Para producción faltan backend seguro, base de datos, autenticación/MFA real, permisos server-side, cifrado, auditoría inmutable, integraciones oficiales EDE/SIGE, infraestructura RFID real, backups, observabilidad, pruebas de seguridad y validación jurídica/técnica final.
