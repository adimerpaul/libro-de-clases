<?php

use App\Support\ImportadorCurriculo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo Curricular 3.1 (Modulo_Curricular_3_1_VERIFICADO): catálogo de referencia del
 * Currículum Nacional — fuentes y licencias, versiones, niveles, cursos, asignaturas, ejes,
 * unidades, OA oficiales, indicadores, habilidades, recursos, plantillas y módulos TP.
 * Se carga con database/data/curriculum_core_demo.sqlite. Las planificaciones e
 * instrumentos siguen en las tablas propias del libro (planificaciones, instrumentos), que
 * apuntan a objetivos_aprendizaje; éste se enlaza al OA oficial con curriculum_objective_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // MySQL no revierte el DDL: si un intento anterior falló a medias (p. ej. sin el .sqlite),
        // quedan tablas creadas sin registrar la migración. Se limpian antes de crearlas de nuevo.
        $this->limpiar();

        Schema::create('curriculum_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url', 500);
            $table->string('source_type', 50);
            $table->string('publisher')->nullable();
            $table->string('license_code', 50)->nullable();
            $table->boolean('commercial_use_allowed')->nullable();
            $table->boolean('derivative_use_allowed')->nullable();
            $table->date('last_verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_versions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->index();
            $table->string('name');
            $table->string('status', 30)->default('REFERENCE');
            $table->date('snapshot_date');
            $table->foreignId('source_id')->nullable()->constrained('curriculum_sources');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->index();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained('curriculum_levels');
            $table->string('code', 40)->index();
            $table->string('name');
            $table->string('track', 30)->default('GENERAL');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->index();
            $table->string('name');
            $table->string('subject_type', 30)->default('ASIGNATURA');
            $table->timestamps();
            $table->softDeletes();
        });

        // grade_subjects del paquete: oferta curricular = versión + curso + asignatura
        Schema::create('curriculum_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_version_id')->constrained('curriculum_versions');
            $table->foreignId('grade_id')->constrained('curriculum_grades');
            $table->foreignId('subject_id')->constrained('curriculum_subjects');
            $table->foreignId('source_id')->nullable()->constrained('curriculum_sources');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['curriculum_version_id', 'grade_id', 'subject_id'], 'curriculum_offering_idx');
        });

        Schema::create('curriculum_axes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained('curriculum_offerings');
            $table->string('code', 80)->nullable();
            $table->string('name');
            $table->string('axis_type', 30)->default('EJE');
            $table->foreignId('source_id')->nullable()->constrained('curriculum_sources');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained('curriculum_offerings');
            $table->string('code', 80);
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('source_id')->nullable()->constrained('curriculum_sources');
            $table->string('import_status', 30)->default('VERIFIED');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('learning_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained('curriculum_offerings');
            $table->foreignId('axis_id')->nullable()->constrained('curriculum_axes');
            $table->string('code', 100)->index();
            $table->unsignedSmallInteger('oa_number')->nullable();
            $table->text('official_text');
            $table->string('priority_type', 40)->nullable();
            $table->foreignId('source_id')->constrained('curriculum_sources');
            $table->boolean('official_record')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['offering_id', 'active']);
        });

        Schema::create('curriculum_unit_objective', function (Blueprint $table) {
            $table->foreignId('unit_id')->constrained('curriculum_units');
            $table->foreignId('objective_id')->constrained('learning_objectives');
            $table->string('relation_type', 30)->default('PRIMARY');
            $table->primary(['unit_id', 'objective_id']);
        });

        Schema::create('curriculum_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained('learning_objectives');
            $table->string('code', 100)->nullable();
            $table->text('text');
            $table->string('source_kind', 40)->default('OFFICIAL');
            $table->foreignId('source_id')->nullable()->constrained('curriculum_sources');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_skills', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('kind', 30)->default('HABILIDAD');
            $table->string('source_kind', 40)->default('PLATFORM');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_objective_skill', function (Blueprint $table) {
            $table->foreignId('objective_id')->constrained('learning_objectives');
            $table->foreignId('skill_id')->constrained('curriculum_skills');
            $table->string('relation_type', 30)->default('DEVELOPS');
            $table->primary(['objective_id', 'skill_id']);
        });

        Schema::create('curriculum_resources', function (Blueprint $table) {
            $table->id();
            $table->string('external_code', 120)->nullable()->index();
            $table->string('title');
            $table->string('resource_type', 50);
            $table->string('format', 50)->nullable();
            $table->foreignId('source_id')->constrained('curriculum_sources');
            $table->string('canonical_url', 500);
            $table->unsignedSmallInteger('year_published')->nullable();
            $table->string('license_code', 50)->nullable();
            $table->boolean('commercial_use_allowed')->nullable();
            $table->boolean('derivative_use_allowed')->nullable();
            $table->string('hosting_policy', 40)->default('LINK_ONLY');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_resource_objective', function (Blueprint $table) {
            $table->foreignId('resource_id')->constrained('curriculum_resources');
            $table->foreignId('objective_id')->constrained('learning_objectives');
            $table->primary(['resource_id', 'objective_id']);
        });

        Schema::create('curriculum_planning_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('offering_id')->nullable()->constrained('curriculum_offerings');
            $table->string('template_type', 30)->default('CLASE');
            $table->json('structure');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_tp_specialties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->index();
            $table->string('name');
            $table->string('sector')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_tp_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained('curriculum_tp_specialties');
            $table->foreignId('grade_id')->constrained('curriculum_grades');
            $table->string('code', 60);
            $table->string('name');
            $table->foreignId('source_id')->nullable()->constrained('curriculum_sources');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_tp_module_objective', function (Blueprint $table) {
            $table->foreignId('module_id')->constrained('curriculum_tp_modules');
            $table->foreignId('objective_id')->constrained('learning_objectives');
            $table->primary(['module_id', 'objective_id']);
        });

        // OA del libro (planificador, evaluaciones, biblioteca) → OA oficial de referencia
        Schema::table('objetivos_aprendizaje', function (Blueprint $table) {
            $table->foreignId('curriculum_objective_id')->nullable()->after('asignatura_id')->constrained('learning_objectives');
        });

        ImportadorCurriculo::cargarReferencia(database_path('data/curriculum_core_demo.sqlite'));
    }

    public function down(): void
    {
        $this->limpiar();
    }

    private function limpiar(): void
    {
        if (Schema::hasColumn('objetivos_aprendizaje', 'curriculum_objective_id')) {
            Schema::table('objetivos_aprendizaje', function (Blueprint $table) {
                $table->dropConstrainedForeignId('curriculum_objective_id');
            });
        }

        foreach ([
            'curriculum_tp_module_objective', 'curriculum_tp_modules', 'curriculum_tp_specialties', 'curriculum_planning_templates',
            'curriculum_resource_objective', 'curriculum_resources', 'curriculum_objective_skill', 'curriculum_skills',
            'curriculum_indicators', 'curriculum_unit_objective', 'learning_objectives', 'curriculum_units', 'curriculum_axes',
            'curriculum_offerings', 'curriculum_subjects', 'curriculum_grades', 'curriculum_levels', 'curriculum_versions', 'curriculum_sources',
        ] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
