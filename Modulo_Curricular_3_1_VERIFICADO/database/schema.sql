BEGIN TRANSACTION;
CREATE TABLE assessment_instrument_items (
  instrument_id INTEGER NOT NULL,
  item_id INTEGER NOT NULL,
  sort_order INTEGER NOT NULL,
  PRIMARY KEY(instrument_id, item_id),
  FOREIGN KEY(instrument_id) REFERENCES assessment_instruments(id),
  FOREIGN KEY(item_id) REFERENCES assessment_items(id)
);
CREATE TABLE assessment_instrument_objectives (
  instrument_id INTEGER NOT NULL,
  objective_id INTEGER NOT NULL,
  weight REAL NOT NULL DEFAULT 1,
  PRIMARY KEY(instrument_id, objective_id),
  FOREIGN KEY(instrument_id) REFERENCES assessment_instruments(id),
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
CREATE TABLE assessment_item_objectives (
  item_id INTEGER NOT NULL,
  objective_id INTEGER NOT NULL,
  weight REAL NOT NULL DEFAULT 1,
  PRIMARY KEY(item_id, objective_id),
  FOREIGN KEY(item_id) REFERENCES assessment_items(id),
  FOREIGN KEY(objective_id) REFERENCES learning_objectives(id)
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
CREATE TABLE grades (
  id INTEGER PRIMARY KEY,
  level_id INTEGER NOT NULL,
  code TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  track TEXT NOT NULL DEFAULT 'GENERAL',
  sort_order INTEGER NOT NULL,
  FOREIGN KEY(level_id) REFERENCES education_levels(id)
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
CREATE TABLE objective_skills (
  objective_id INTEGER NOT NULL,
  skill_id INTEGER NOT NULL,
  relation_type TEXT NOT NULL DEFAULT 'DEVELOPS',
  PRIMARY KEY(objective_id, skill_id),
  FOREIGN KEY(objective_id) REFERENCES learning_objectives(id),
  FOREIGN KEY(skill_id) REFERENCES skills_competencies(id)
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
CREATE TABLE skills_competencies (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  description TEXT,
  kind TEXT NOT NULL DEFAULT 'HABILIDAD',
  source_kind TEXT NOT NULL DEFAULT 'PLATFORM'
);
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
CREATE TABLE subjects (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  subject_type TEXT NOT NULL DEFAULT 'ASIGNATURA'
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
CREATE TABLE tp_module_objectives (
  module_id INTEGER NOT NULL,
  objective_id INTEGER NOT NULL,
  PRIMARY KEY(module_id, objective_id),
  FOREIGN KEY(module_id) REFERENCES tp_modules(id),
  FOREIGN KEY(objective_id) REFERENCES learning_objectives(id)
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
CREATE TABLE tp_specialties (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  sector TEXT
);
CREATE TABLE unit_objectives (
  unit_id INTEGER NOT NULL,
  objective_id INTEGER NOT NULL,
  relation_type TEXT NOT NULL DEFAULT 'PRIMARY',
  PRIMARY KEY(unit_id, objective_id),
  FOREIGN KEY(unit_id) REFERENCES curriculum_units(id),
  FOREIGN KEY(objective_id) REFERENCES learning_objectives(id)
);
CREATE INDEX idx_oa_grade_subject ON learning_objectives(grade_subject_id);
CREATE INDEX idx_resources_source ON resource_catalog(source_id);
CREATE INDEX idx_items_type ON assessment_items(item_type);
CREATE INDEX idx_plans_grade_subject ON teacher_plans(grade_subject_id);
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
COMMIT;
