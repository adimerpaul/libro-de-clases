# Base de datos de demostración

## Uso inmediato
La base `curriculum_core_demo.sqlite` ya viene creada y se puede abrir con SQLite, DB Browser for SQLite o desde código.

## Reconstruir desde cero
Desde la raíz del paquete:

```bash
python3 database/build_sqlite.py
```

El script crea/reemplaza automáticamente:

`database/curriculum_core_demo.sqlite`

## Prueba rápida con Python

```bash
python3 - <<'PY'
import sqlite3
con = sqlite3.connect('database/curriculum_core_demo.sqlite')
print(con.execute('SELECT * FROM v_curriculum_selector').fetchall())
print(con.execute('SELECT oa_code, official_text FROM v_oa_bundle').fetchall())
PY
```
