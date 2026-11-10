import sqlite3, os, json
from datetime import datetime

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(BASE_DIR, 'curriculum_core_demo.sqlite')
os.makedirs(BASE_DIR, exist_ok=True)
if os.path.exists(OUT): os.remove(OUT)
con = sqlite3.connect(OUT)
con.execute('PRAGMA foreign_keys = ON')
cur = con.cursor()

schema = r'''
CREATE TABLE source_registry (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  url TEXT NOT NULL UNIQUE,
  source_type TEXT NOT NULL,
  publisher TEXT,
  license_code TEXT,
  commercial_use_allowed INTEGER,
  derivative_use_allowed INTEGER,
  last_verified_at TEXT,
  notes TEXT
);
CREATE TABLE curriculum_versions (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'REFERENCE',
  snapshot_date TEXT NOT NULL,
  source_id INTEGER,
  FOREIGN KEY(source_id) REFERENCES source_registry(id)
);
CREATE TABLE education_levels (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  sort_order INTEGER NOT NULL
);
CREATE TABLE grades (
  id INTEGER PRIMARY KEY,
  level_id INTEGER NOT NULL,
  code TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  track TEXT NOT NULL DEFAULT 'GENERAL',
  sort_order INTEGER NOT NULL,
  FOREIGN KEY(level_id) REFERENCES education_levels(id)
);
CREATE TABLE subjects (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  subject_type TEXT NOT NULL DEFAULT 'ASIGNATURA'
);
CREATE TABLE grade_subjects (
  id INTEGER PRIMARY KEY,
  curriculum_version_id INTEGER NOT NULL,
  grade_id INTEGER NOT NULL,
  subject_id INTEGER NOT NULL,
  source_id INTEGER,
  active INTEGER NOT NULL DEFAULT 1,
  UNIQUE(curriculum_version_id, grade_id, subject_id),
  FOREIGN KEY(curriculum_version_id) REFERENCES curriculum_versions(id),
  FOREIGN KEY(grade_id) REFERENCES grades(id),
  FOREIGN KEY(subject_id) REFERENCES subjects(id),
  FOREIGN KEY(source_id) REFERENCES source_registry(id)
);
CREATE TABLE curriculum_axes (
  id INTEGER PRIMARY KEY,
  grade_subject_id INTEGER NOT NULL,
  code TEXT,
  name TEXT NOT NULL,
  axis_type TEXT NOT NULL DEFAULT 'EJE',
  source_id INTEGER,
  FOREIGN KEY(grade_subject_id) REFERENCES grade_subjects(id),
  FOREIGN KEY(source_id) REFERENCES source_registry(id)
);
CREATE TABLE curriculum_units (
  id INTEGER PRIMARY KEY,
  grade_subject_id INTEGER NOT NULL,
  code TEXT NOT NULL,
  name TEXT NOT NULL,
  sort_order INTEGER NOT NULL,
  source_id INTEGER,
  import_status TEXT NOT NULL DEFAULT 'VERIFIED',
  UNIQUE(grade_subject_id, code),
  FOREIGN KEY(grade_subject_id) REFERENCES grade_subjects(id),
  FOREIGN KEY(source_id) REFERENCES source_registry(id)
);
CREATE TABLE learning_objectives (
  id INTEGER PRIMARY KEY,
  grade_subject_id INTEGER NOT NULL,
  axis_id INTEGER,
  code TEXT NOT NULL UNIQUE,
  oa_number INTEGER,
  official_text TEXT NOT NULL,
  priority_type TEXT,
  source_id INTEGER NOT NULL,
  official_record INTEGER NOT NULL DEFAULT 1,
  active INTEGER NOT NULL DEFAULT 1,
  FOREIGN KEY(grade_subject_id) REFERENCES grade_subjects(id),
  FOREIGN KEY(axis_id) REFERENCES curriculum_axes(id),
  FOREIGN KEY(source_id) REFERENCES source_registry(id)
);
CREATE TABLE unit_objectives (
  unit_id INTEGER NOT NULL,
  objective_id INTEGER NOT NULL,
  relation_type TEXT NOT NULL DEFAULT 'PRIMARY',
  PRIMARY KEY(unit_id, objective_id),
  FOREIGN KEY(unit_id) REFERENCES curriculum_units(id),
  FOREIGN KEY(objective_id) REFERENCES learning_objectives(id)
);
CREATE TABLE indicators (
  id INTEGER PRIMARY KEY,
  objective_id INTEGER NOT NULL,
  code TEXT,
  text TEXT NOT NULL,
  source_kind TEXT NOT NULL DEFAULT 'OFFICIAL',
  source_id INTEGER,
  FOREIGN KEY(objective_id) REFERENCES learning_objectives(id),
  FOREIGN KEY(source_id) REFERENCES source_registry(id)
);
CREATE TABLE skills_competencies (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  description TEXT,
  kind TEXT NOT NULL DEFAULT 'HABILIDAD',
  source_kind TEXT NOT NULL DEFAULT 'PLATFORM'
);
CREATE TABLE objective_skills (
  objective_id INTEGER NOT NULL,
  skill_id INTEGER NOT NULL,
  relation_type TEXT NOT NULL DEFAULT 'DEVELOPS',
  PRIMARY KEY(objective_id, skill_id),
  FOREIGN KEY(objective_id) REFERENCES learning_objectives(id),
  FOREIGN KEY(skill_id) REFERENCES skills_competencies(id)
);
CREATE TABLE resource_catalog (
  id INTEGER PRIMARY KEY,
  external_code TEXT,
  title TEXT NOT NULL,
  resource_type TEXT NOT NULL,
  format TEXT,
  source_id INTEGER NOT NULL,
  canonical_url TEXT NOT NULL,
  year_published INTEGER,
  license_code TEXT,
  commercial_use_allowed INTEGER,
  derivative_use_allowed INTEGER,
  hosting_policy TEXT NOT NULL DEFAULT 'LINK_ONLY',
  metadata_json TEXT,
  FOREIGN KEY(source_id) REFERENCES source_registry(id)
);
CREATE TABLE resource_objectives (
  resource_id INTEGER NOT NULL,
  objective_id INTEGER NOT NULL,
  PRIMARY KEY(resource_id, objective_id),
  FOREIGN KEY(resource_id) REFERENCES resource_catalog(id),
  FOREIGN KEY(objective_id) REFERENCES learning_objectives(id)
);
CREATE TABLE planning_templates (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  grade_subject_id INTEGER,
  template_type TEXT NOT NULL DEFAULT 'CLASE',
  structure_json TEXT NOT NULL,
  active INTEGER NOT NULL DEFAULT 1,
  FOREIGN KEY(grade_subject_id) REFERENCES grade_subjects(id)
);
CREATE TABLE teacher_plans (
  id INTEGER PRIMARY KEY,
  school_id INTEGER,
  teacher_user_id INTEGER,
  grade_subject_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  plan_type TEXT NOT NULL DEFAULT 'CLASE',
  start_date TEXT,
  end_date TEXT,
  minutes INTEGER,
  content_text TEXT,
  success_criteria TEXT,
  opening_strategy TEXT,
  development_strategy TEXT,
  closure_strategy TEXT,
  assessment_strategy TEXT,
  resources_text TEXT,
  organization_time_space TEXT,
  dua_adjustments TEXT,
  status TEXT NOT NULL DEFAULT 'DRAFT',
  created_at TEXT NOT NULL,
  FOREIGN KEY(grade_subject_id) REFERENCES grade_subjects(id)
);
CREATE TABLE teacher_plan_objectives (
  plan_id INTEGER NOT NULL,
  objective_id INTEGER NOT NULL,
  is_primary INTEGER NOT NULL DEFAULT 1,
  PRIMARY KEY(plan_id, objective_id),
  FOREIGN KEY(plan_id) REFERENCES teacher_plans(id),
  FOREIGN KEY(objective_id) REFERENCES learning_objectives(id)
);
CREATE TABLE teacher_plan_resources (
  plan_id INTEGER NOT NULL,
  resource_id INTEGER NOT NULL,
  usage_note TEXT,
  PRIMARY KEY(plan_id, resource_id),
  FOREIGN KEY(plan_id) REFERENCES teacher_plans(id),
  FOREIGN KEY(resource_id) REFERENCES resource_catalog(id)
);
CREATE TABLE assessment_items (
  id INTEGER PRIMARY KEY,
  source_kind TEXT NOT NULL DEFAULT 'PLATFORM_GENERATED',
  source_id INTEGER,
  item_type TEXT NOT NULL,
  stem TEXT NOT NULL,
  depth_level TEXT,
  difficulty TEXT,
  max_score REAL NOT NULL DEFAULT 1,
  answer_key_json TEXT,
  rubric_json TEXT,
  license_code TEXT,
  canonical_url TEXT,
  active INTEGER NOT NULL DEFAULT 1,
  FOREIGN KEY(source_id) REFERENCES source_registry(id)
);
CREATE TABLE assessment_item_objectives (
  item_id INTEGER NOT NULL,
  objective_id INTEGER NOT NULL,
  weight REAL NOT NULL DEFAULT 1,
  PRIMARY KEY(item_id, objective_id),
  FOREIGN KEY(item_id) REFERENCES assessment_items(id),
  FOREIGN KEY(objective_id) REFERENCES learning_objectives(id)
);
CREATE TABLE assessment_instruments (
  id INTEGER PRIMARY KEY,
  teacher_user_id INTEGER,
  grade_subject_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  purpose TEXT NOT NULL DEFAULT 'FORMATIVA',
  instrument_type TEXT NOT NULL,
  difficulty_profile TEXT,
  depth_profile TEXT,
  exigency_percent REAL NOT NULL DEFAULT 60,
  status TEXT NOT NULL DEFAULT 'DRAFT',
  parameters_json TEXT,
  created_at TEXT NOT NULL,
  FOREIGN KEY(grade_subject_id) REFERENCES grade_subjects(id)
);
CREATE TABLE assessment_instrument_objectives (
  instrument_id INTEGER NOT NULL,
  objective_id INTEGER NOT NULL,
  weight REAL NOT NULL DEFAULT 1,
  PRIMARY KEY(instrument_id, objective_id),
  FOREIGN KEY(instrument_id) REFERENCES assessment_instruments(id),
  FOREIGN KEY(objective_id) REFERENCES learning_objectives(id)
);
CREATE TABLE assessment_instrument_items (
  instrument_id INTEGER NOT NULL,
  item_id INTEGER NOT NULL,
  sort_order INTEGER NOT NULL,
  PRIMARY KEY(instrument_id, item_id),
  FOREIGN KEY(instrument_id) REFERENCES assessment_instruments(id),
  FOREIGN KEY(item_id) REFERENCES assessment_items(id)
);
CREATE TABLE assessment_results (
  id INTEGER PRIMARY KEY,
  instrument_id INTEGER NOT NULL,
  student_id INTEGER NOT NULL,
  raw_score REAL,
  percentage REAL,
  grade REAL,
  correction_status TEXT NOT NULL DEFAULT 'PENDING',
  feedback TEXT,
  corrected_at TEXT,
  FOREIGN KEY(instrument_id) REFERENCES assessment_instruments(id)
);
CREATE TABLE tp_specialties (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  sector TEXT
);
CREATE TABLE tp_modules (
  id INTEGER PRIMARY KEY,
  specialty_id INTEGER NOT NULL,
  grade_id INTEGER NOT NULL,
  code TEXT NOT NULL,
  name TEXT NOT NULL,
  source_id INTEGER,
  UNIQUE(specialty_id, grade_id, code),
  FOREIGN KEY(specialty_id) REFERENCES tp_specialties(id),
  FOREIGN KEY(grade_id) REFERENCES grades(id),
  FOREIGN KEY(source_id) REFERENCES source_registry(id)
);
CREATE TABLE tp_module_objectives (
  module_id INTEGER NOT NULL,
  objective_id INTEGER NOT NULL,
  PRIMARY KEY(module_id, objective_id),
  FOREIGN KEY(module_id) REFERENCES tp_modules(id),
  FOREIGN KEY(objective_id) REFERENCES learning_objectives(id)
);
CREATE TABLE import_runs (
  id INTEGER PRIMARY KEY,
  source_id INTEGER NOT NULL,
  started_at TEXT NOT NULL,
  finished_at TEXT,
  status TEXT NOT NULL,
  records_seen INTEGER NOT NULL DEFAULT 0,
  records_created INTEGER NOT NULL DEFAULT 0,
  records_updated INTEGER NOT NULL DEFAULT 0,
  error_log TEXT,
  FOREIGN KEY(source_id) REFERENCES source_registry(id)
);
CREATE INDEX idx_oa_grade_subject ON learning_objectives(grade_subject_id);
CREATE INDEX idx_resources_source ON resource_catalog(source_id);
CREATE INDEX idx_items_type ON assessment_items(item_type);
CREATE INDEX idx_plans_grade_subject ON teacher_plans(grade_subject_id);
'''
cur.executescript(schema)

# Sources verified via public pages on 2026-11-02
sources = [
(1,'Currículum Nacional - 1° a 6° Básico','https://www.curriculumnacional.cl/curriculum/1o-6o-basico','CURRICULUM_INDEX','MINEDUC / UCE',None,None,None,'2026-11-02','Índice curricular oficial; verificar licencia por recurso.'),
(2,'Bases Curriculares Artes Visuales 1° Básico','https://www.curriculumnacional.cl/614/articles-22394_bases.pdf','BASE_CURRICULAR','MINEDUC / UCE',None,None,None,'2026-11-02','Fuente de OA semilla de Artes Visuales 1° Básico.'),
(3,'Programa de Estudio Artes Visuales 1°','https://www.curriculumnacional.cl/portal/Documentos-Curriculares/Programas/20746%3APrograma-de-Estudio-Artes-Visuales-de-1','PROGRAMA_ESTUDIO','MINEDUC / UCE','CC-BY-NC-ND',0,0,'2026-11-02','Uso comercial no permitido según ficha pública consultada.'),
(4,'AR-01-OA-1-U1-A10','https://www.curriculumnacional.cl/recursos/ar-01-oa-1-u1-a10','RECURSO','MINEDUC / UCE','CC-BY-NC-SA',0,1,'2026-11-02','Guardar metadatos y enlace; no rehostear en producto comercial sin autorización.'),
(5,'Vida cotidiana','https://www.curriculumnacional.cl/recursos/vida-cotidiana','RECURSO','MINEDUC / UCE','CC-BY-NC-SA',0,1,'2026-11-02','Actividad sugerida vinculada a AR01 OA 01.'),
(6,'La familia en la escultura','https://www.curriculumnacional.cl/recursos/familia-escultura','RECURSO','MINEDUC / UCE',None,None,None,'2026-11-02','Verificar licencia en ficha antes de reutilizar contenido.'),
(7,'Cursos y niveles Currículum Nacional','https://www.curriculumnacional.cl/curriculum/cursos-y-niveles','CURRICULUM_INDEX','MINEDUC / UCE',None,None,None,'2026-11-02','Fuente estructural de niveles/cursos.'),
(8,'Arma tu Evaluación - referencia funcional','https://www.curriculumnacional.cl/evaluacion/arma-tu-evaluacion','EVALUATION_TOOL','MINEDUC / UCE',None,None,None,'2026-11-02','Referencia funcional; banco oficial de preguntas no se copia en esta demo.'),
]
cur.executemany('INSERT INTO source_registry VALUES (?,?,?,?,?,?,?,?,?,?)', sources)

versions=[(1,'CN_1_6_SNAPSHOT_2026_11_02','Currículum Nacional 1° a 6° Básico - snapshot de integración','REFERENCE','2026-11-02',1)]
cur.executemany('INSERT INTO curriculum_versions VALUES (?,?,?,?,?,?)', versions)

levels=[
(1,'PARV','Educación Parvularia',10),(2,'BASICA_1_6','Educación Básica 1° a 6°',20),(3,'BASICA_7_8_MEDIA_1_2','7° Básico a 2° Medio',30),(4,'MEDIA_3_4_FG_HC','3° y 4° Medio FG/HC',40),(5,'MEDIA_TP','3° y 4° Medio TP',50),(6,'EPJA','Educación de Personas Jóvenes y Adultas',60)]
cur.executemany('INSERT INTO education_levels VALUES (?,?,?,?)', levels)

grades=[
(1,1,'SC','Sala Cuna','PARVULARIA',1),(2,1,'NM','Nivel Medio','PARVULARIA',2),(3,1,'NT','Nivel Transición','PARVULARIA',3),
(4,2,'1B','1° Básico','GENERAL',10),(5,2,'2B','2° Básico','GENERAL',20),(6,2,'3B','3° Básico','GENERAL',30),(7,2,'4B','4° Básico','GENERAL',40),(8,2,'5B','5° Básico','GENERAL',50),(9,2,'6B','6° Básico','GENERAL',60),
(10,3,'7B','7° Básico','GENERAL',70),(11,3,'8B','8° Básico','GENERAL',80),(12,3,'1M','1° Medio','GENERAL',90),(13,3,'2M','2° Medio','GENERAL',100),
(14,4,'3M_FG','3° Medio FG','FG',110),(15,4,'3M_HC','3° Medio HC','HC',120),(16,4,'4M_FG','4° Medio FG','FG',130),(17,4,'4M_HC','4° Medio HC','HC',140),
(18,5,'3M_TP','3° Medio TP','TP',150),(19,5,'4M_TP','4° Medio TP','TP',160)]
cur.executemany('INSERT INTO grades VALUES (?,?,?,?,?,?)', grades)

subjects=[
(1,'ART_VIS','Artes Visuales','ASIGNATURA'),(2,'LENG','Lengua y Literatura','ASIGNATURA'),(3,'MAT','Matemática','ASIGNATURA'),(4,'CN','Ciencias Naturales','ASIGNATURA'),(5,'HIST','Historia, Geografía y Ciencias Sociales','ASIGNATURA'),(6,'ING','Inglés','ASIGNATURA'),
(7,'PARV_COM_INT','Comunicación integral','AMBITO'),(8,'PARV_DPS','Desarrollo personal y social','AMBITO'),(9,'PARV_ICE','Interacción y comprensión del entorno','AMBITO')]
cur.executemany('INSERT INTO subjects VALUES (?,?,?,?)', subjects)
cur.execute('INSERT INTO grade_subjects VALUES (1,1,4,1,1,1)')
cur.execute("INSERT INTO curriculum_axes VALUES (1,1,'EXP_CREAR','Expresar y crear visualmente','EJE',2)")
cur.execute("INSERT INTO curriculum_axes VALUES (2,1,'APREC_RESP','Apreciar y responder frente al arte','EJE',2)")
cur.execute("INSERT INTO curriculum_units VALUES (1,1,'U1','Materiales y herramientas',1,3,'VERIFIED')")
cur.execute("INSERT INTO curriculum_units VALUES (2,1,'U2','Unidad 2',2,4,'PARTIAL_METADATA')")

# OA seed, sourced from official Bases page. Keep immutable and source-linked.
oas=[
(1,1,1,'AR01-OA-01',1,'Expresar y crear trabajos de arte a partir de la observación del entorno natural, cultural y artístico, considerando paisajes, animales, plantas, vida cotidiana, familia y obras de arte de distintos contextos.',None,2,1,1),
(2,1,1,'AR01-OA-02',2,'Experimentar y aplicar elementos del lenguaje visual en trabajos de arte, incluyendo distintos tipos de línea, color y textura.',None,2,1,1),
(3,1,1,'AR01-OA-03',3,'Expresar emociones e ideas mediante la experimentación con materiales, herramientas y procedimientos de creación visual, incluyendo medios tradicionales y digitales.',None,2,1,1),
(4,1,2,'AR01-OA-04',4,'Observar y comunicar oralmente primeras impresiones, sentimientos e ideas frente a obras de arte presentadas por diversos medios.',None,2,1,1),
(5,1,2,'AR01-OA-05',5,'Explicar preferencias frente al trabajo artístico personal y de sus pares utilizando elementos del lenguaje visual.',None,2,1,1),
]
cur.executemany('INSERT INTO learning_objectives VALUES (?,?,?,?,?,?,?,?,?,?)', oas)
cur.executemany('INSERT INTO unit_objectives VALUES (?,?,?)', [(1,1,'PRIMARY'),(1,5,'SECONDARY'),(2,1,'PRIMARY')])

skills=[
(1,'OBSERVAR','Observación','Observar referentes visuales y reconocer elementos relevantes.','HABILIDAD','PLATFORM'),
(2,'CREAR','Creación visual','Producir respuestas visuales a partir de ideas, experiencias y referentes.','HABILIDAD','PLATFORM'),
(3,'COMUNICAR','Comunicación','Expresar oralmente ideas, impresiones y preferencias.','HABILIDAD','PLATFORM'),
(4,'EXPERIMENTAR','Experimentación','Explorar materiales, herramientas, color, línea y textura.','HABILIDAD','PLATFORM')]
cur.executemany('INSERT INTO skills_competencies VALUES (?,?,?,?,?,?)', skills)
cur.executemany('INSERT INTO objective_skills VALUES (?,?,?)', [(1,1,'DEVELOPS'),(1,2,'DEVELOPS'),(2,4,'DEVELOPS'),(3,2,'DEVELOPS'),(3,4,'DEVELOPS'),(4,1,'DEVELOPS'),(4,3,'DEVELOPS'),(5,3,'DEVELOPS')])

resources=[
(1,'AR-01-OA-1-U1-A10','AR-01-OA-1-U1-A10','ACTIVIDAD_SUGERIDA','DOCX/PDF',4,'https://www.curriculumnacional.cl/recursos/ar-01-oa-1-u1-a10',2020,'CC-BY-NC-SA',0,1,'LINK_ONLY',json.dumps({'course':'1° Básico','subject':'Artes Visuales','axis':'Expresar y crear visualmente'},ensure_ascii=False)),
(2,'AR-01-OA-1-U1-A1','Vida cotidiana','ACTIVIDAD_SUGERIDA','DOCX/PDF',5,'https://www.curriculumnacional.cl/recursos/vida-cotidiana',2020,'CC-BY-NC-SA',0,1,'LINK_ONLY',json.dumps({'course':'1° Básico','subject':'Artes Visuales','axis':'Expresar y crear visualmente'},ensure_ascii=False)),
(3,None,'La familia en la escultura','PRESENTACION','PPTX',6,'https://www.curriculumnacional.cl/recursos/familia-escultura',2022,None,None,None,'LINK_ONLY',json.dumps({'course':'1° Básico','subject':'Artes Visuales','oa':['AR01-OA-01','AR01-OA-04']},ensure_ascii=False)),
]
cur.executemany('INSERT INTO resource_catalog VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', resources)
cur.executemany('INSERT INTO resource_objectives VALUES (?,?)', [(1,1),(2,1),(3,1),(3,4)])

# Platform-created suggestions: clearly not official Mineduc indicators.
indicators=[
(1,1,'PLAT-AR01-01-I1','Selecciona referentes del entorno para proponer una creación visual propia.','PLATFORM_SUGGESTED',None),
(2,1,'PLAT-AR01-01-I2','Comunica oralmente qué observó y qué decidió representar en su trabajo.','PLATFORM_SUGGESTED',None),
(3,2,'PLAT-AR01-02-I1','Usa al menos dos tipos de línea y diferencia colores o texturas en una producción.','PLATFORM_SUGGESTED',None),
]
cur.executemany('INSERT INTO indicators VALUES (?,?,?,?,?,?)', indicators)

plan_template={
 'sections':['OA seleccionados','Contenidos','Habilidades/competencias','Indicadores','Inicio','Desarrollo','Cierre','Recursos','Evaluación/evidencia','DUA y adecuaciones','Tiempo y espacio'],
 'defaults':{'opening_minutes':10,'development_minutes':55,'closure_minutes':15}
}
cur.execute('INSERT INTO planning_templates VALUES (1,?,?,?,?,1)',('Planificación de clase desde OA',1,'CLASE',json.dumps(plan_template,ensure_ascii=False)))

# Sample teacher plan tied to OA 1.
cur.execute('''INSERT INTO teacher_plans
(id,school_id,teacher_user_id,grade_subject_id,title,plan_type,start_date,end_date,minutes,content_text,success_criteria,opening_strategy,development_strategy,closure_strategy,assessment_strategy,resources_text,organization_time_space,dua_adjustments,status,created_at)
VALUES (1,1,101,1,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)''',(
 'Exploramos nuestro entorno y lo transformamos en arte','CLASE','2026-11-05','2026-11-05',80,
 'Observación del entorno natural, cultural y artístico como punto de partida para una creación visual.',
 'El estudiante selecciona un referente, crea una representación y explica una decisión visual.',
 'Activación de conocimientos previos mediante imágenes del entorno y conversación guiada.',
 'Observación, boceto y creación usando materiales disponibles; acompañamiento con opciones DUA.',
 'Galería breve, autoexplicación y ticket de salida oral/visual.',
 'Lista de cotejo breve vinculada al OA y evidencia fotográfica del proceso.',
 'Materiales de dibujo/pintura y enlaces oficiales asociados al OA.',
 '10 min inicio · 55 min desarrollo · 15 min cierre; aula organizada en estaciones.',
 'Opciones de respuesta oral/visual; apoyos de modelado y elección de materiales.',
 'DRAFT',datetime.utcnow().isoformat(timespec='seconds')+'Z'))
cur.execute('INSERT INTO teacher_plan_objectives VALUES (1,1,1)')
cur.execute('INSERT INTO teacher_plan_resources VALUES (1,2,?)',('Usar como referencia; respetar licencia y enlazar a fuente oficial.',))

# Demo evaluation items are platform-created, not copied from Mineduc question bank.
items=[
(1,'PLATFORM_GENERATED',None,'MULTIPLE_CHOICE','¿Cuál opción describe mejor un referente del entorno natural que podría inspirar un trabajo de arte?','SUPERFICIAL','BAJA',1,json.dumps({'correct_option':'B','options':['Una contraseña','Un paisaje con árboles','Una tabla de notas','Un horario']},ensure_ascii=False),None,None,None,1),
(2,'PLATFORM_GENERATED',None,'OPEN_RESPONSE','Describe una característica del entorno que elegiste representar y explica una decisión visual de tu trabajo.','MEDIA','MEDIA',3,None,json.dumps({'criteria':['Identifica un referente','Explica una decisión visual','Comunica con claridad']},ensure_ascii=False),None,None,1)
]
cur.executemany('INSERT INTO assessment_items VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', items)
cur.executemany('INSERT INTO assessment_item_objectives VALUES (?,?,?)', [(1,1,1),(2,1,1)])
cur.execute('''INSERT INTO assessment_instruments VALUES
(1,101,1,'Evaluación formativa OA 1 · Artes Visuales 1° Básico','FORMATIVA','MIXTA','BAJA-MEDIA','SUPERFICIAL-MEDIA',60,'DRAFT',?,?)''',(json.dumps({'course':'1° Básico','subject':'Artes Visuales','oa':['AR01-OA-01'],'item_count':2},ensure_ascii=False),datetime.utcnow().isoformat(timespec='seconds')+'Z'))
cur.execute('INSERT INTO assessment_instrument_objectives VALUES (1,1,1)')
cur.executemany('INSERT INTO assessment_instrument_items VALUES (?,?,?)',[(1,1,1),(1,2,2)])

con.commit()

# Useful views for Laravel/API consumption
cur.executescript('''
CREATE VIEW v_curriculum_selector AS
SELECT gs.id AS grade_subject_id, g.code AS grade_code, g.name AS grade_name, g.track,
       s.code AS subject_code, s.name AS subject_name, cv.code AS curriculum_version,
       COUNT(lo.id) AS oa_count
FROM grade_subjects gs
JOIN grades g ON g.id=gs.grade_id
JOIN subjects s ON s.id=gs.subject_id
JOIN curriculum_versions cv ON cv.id=gs.curriculum_version_id
LEFT JOIN learning_objectives lo ON lo.grade_subject_id=gs.id AND lo.active=1
WHERE gs.active=1
GROUP BY gs.id,g.code,g.name,g.track,s.code,s.name,cv.code;

CREATE VIEW v_oa_bundle AS
SELECT lo.id AS objective_id, lo.code AS oa_code, lo.official_text,
       g.name AS grade_name, s.name AS subject_name, ca.name AS axis_name,
       sr.url AS source_url, sr.license_code,
       (SELECT COUNT(*) FROM resource_objectives ro WHERE ro.objective_id=lo.id) AS resource_count,
       (SELECT COUNT(*) FROM indicators i WHERE i.objective_id=lo.id) AS indicator_count
FROM learning_objectives lo
JOIN grade_subjects gs ON gs.id=lo.grade_subject_id
JOIN grades g ON g.id=gs.grade_id
JOIN subjects s ON s.id=gs.subject_id
LEFT JOIN curriculum_axes ca ON ca.id=lo.axis_id
JOIN source_registry sr ON sr.id=lo.source_id;
''')
con.commit()

# sanity checks
checks = {
 'levels': cur.execute('select count(*) from education_levels').fetchone()[0],
 'grades': cur.execute('select count(*) from grades').fetchone()[0],
 'oas': cur.execute('select count(*) from learning_objectives').fetchone()[0],
 'resources': cur.execute('select count(*) from resource_catalog').fetchone()[0],
 'plans': cur.execute('select count(*) from teacher_plans').fetchone()[0],
 'instruments': cur.execute('select count(*) from assessment_instruments').fetchone()[0],
}
print(json.dumps(checks,ensure_ascii=False))
con.close()
