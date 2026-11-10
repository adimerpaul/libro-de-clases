-- Selector Curso → Asignatura
SELECT * FROM v_curriculum_selector ORDER BY grade_name, subject_name;

-- OA de Artes Visuales 1° Básico
SELECT * FROM v_oa_bundle WHERE grade_name = '1° Básico' AND subject_name = 'Artes Visuales';

-- Recursos del OA 1
SELECT r.title, r.resource_type, r.canonical_url, r.license_code, r.hosting_policy
FROM resource_catalog r
JOIN resource_objectives ro ON ro.resource_id = r.id
JOIN learning_objectives oa ON oa.id = ro.objective_id
WHERE oa.code = 'AR01-OA-01';

-- Alineación de una planificación con sus OA
SELECT p.title, oa.code, oa.official_text
FROM teacher_plans p
JOIN teacher_plan_objectives po ON po.plan_id=p.id
JOIN learning_objectives oa ON oa.id=po.objective_id
WHERE p.id=1;

-- Alineación de instrumento ↔ OA
SELECT ai.title, oa.code, COUNT(ii.item_id) AS items
FROM assessment_instruments ai
JOIN assessment_instrument_objectives aio ON aio.instrument_id=ai.id
JOIN learning_objectives oa ON oa.id=aio.objective_id
LEFT JOIN assessment_instrument_items ii ON ii.instrument_id=ai.id
GROUP BY ai.id, oa.id;
