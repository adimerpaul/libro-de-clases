<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('curriculum_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('url')->unique();
            $table->string('source_type', 50);
            $table->string('publisher')->nullable();
            $table->string('license_code', 50)->nullable();
            $table->boolean('commercial_use_allowed')->nullable();
            $table->boolean('derivative_use_allowed')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('curriculum_versions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('status', 30)->default('REFERENCE');
            $table->date('snapshot_date');
            $table->foreignId('source_id')->nullable()->constrained('curriculum_sources')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('curriculum_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('curriculum_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained('curriculum_levels')->cascadeOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('track', 30)->default('GENERAL');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('curriculum_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->string('subject_type', 30)->default('ASIGNATURA');
            $table->timestamps();
        });

        Schema::create('curriculum_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_version_id')->constrained('curriculum_versions')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('curriculum_grades')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('curriculum_subjects')->cascadeOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('curriculum_sources')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->unique(['curriculum_version_id','grade_id','subject_id'], 'curriculum_offering_unique');
            $table->timestamps();
        });

        Schema::create('curriculum_axes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained('curriculum_offerings')->cascadeOnDelete();
            $table->string('code', 80)->nullable();
            $table->string('name');
            $table->string('axis_type', 30)->default('EJE');
            $table->foreignId('source_id')->nullable()->constrained('curriculum_sources')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('curriculum_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained('curriculum_offerings')->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('source_id')->nullable()->constrained('curriculum_sources')->nullOnDelete();
            $table->string('import_status', 30)->default('VERIFIED');
            $table->unique(['offering_id','code']);
            $table->timestamps();
        });

        Schema::create('learning_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offering_id')->constrained('curriculum_offerings')->cascadeOnDelete();
            $table->foreignId('axis_id')->nullable()->constrained('curriculum_axes')->nullOnDelete();
            $table->string('code', 100)->unique();
            $table->unsignedSmallInteger('oa_number')->nullable();
            $table->text('official_text');
            $table->string('priority_type', 40)->nullable();
            $table->foreignId('source_id')->constrained('curriculum_sources')->restrictOnDelete();
            $table->boolean('official_record')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['offering_id','active']);
        });

        Schema::create('curriculum_unit_objective', function (Blueprint $table) {
            $table->foreignId('unit_id')->constrained('curriculum_units')->cascadeOnDelete();
            $table->foreignId('objective_id')->constrained('learning_objectives')->cascadeOnDelete();
            $table->string('relation_type', 30)->default('PRIMARY');
            $table->primary(['unit_id','objective_id']);
        });

        Schema::create('curriculum_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained('learning_objectives')->cascadeOnDelete();
            $table->string('code', 100)->nullable();
            $table->text('text');
            $table->string('source_kind', 40)->default('OFFICIAL');
            $table->foreignId('source_id')->nullable()->constrained('curriculum_sources')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('curriculum_skills', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('kind', 30)->default('HABILIDAD');
            $table->string('source_kind', 40)->default('PLATFORM');
            $table->timestamps();
        });

        Schema::create('curriculum_objective_skill', function (Blueprint $table) {
            $table->foreignId('objective_id')->constrained('learning_objectives')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('curriculum_skills')->cascadeOnDelete();
            $table->string('relation_type', 30)->default('DEVELOPS');
            $table->primary(['objective_id','skill_id']);
        });

        Schema::create('curriculum_resources', function (Blueprint $table) {
            $table->id();
            $table->string('external_code', 120)->nullable()->index();
            $table->string('title');
            $table->string('resource_type', 50);
            $table->string('format', 50)->nullable();
            $table->foreignId('source_id')->constrained('curriculum_sources')->restrictOnDelete();
            $table->text('canonical_url');
            $table->unsignedSmallInteger('year_published')->nullable();
            $table->string('license_code', 50)->nullable();
            $table->boolean('commercial_use_allowed')->nullable();
            $table->boolean('derivative_use_allowed')->nullable();
            $table->string('hosting_policy', 40)->default('LINK_ONLY');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('curriculum_resource_objective', function (Blueprint $table) {
            $table->foreignId('resource_id')->constrained('curriculum_resources')->cascadeOnDelete();
            $table->foreignId('objective_id')->constrained('learning_objectives')->cascadeOnDelete();
            $table->primary(['resource_id','objective_id']);
        });

        Schema::create('teacher_plan_objectives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_plan_id'); // adaptar FK a tabla real de planificaciones
            $table->foreignId('objective_id')->constrained('learning_objectives')->restrictOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->unique(['teacher_plan_id','objective_id']);
            $table->timestamps();
        });

        Schema::create('assessment_instrument_objectives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_instrument_id'); // adaptar FK a tabla real
            $table->foreignId('objective_id')->constrained('learning_objectives')->restrictOnDelete();
            $table->decimal('weight', 6, 3)->default(1);
            $table->unique(['assessment_instrument_id','objective_id'], 'assessment_instrument_oa_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_instrument_objectives');
        Schema::dropIfExists('teacher_plan_objectives');
        Schema::dropIfExists('curriculum_resource_objective');
        Schema::dropIfExists('curriculum_resources');
        Schema::dropIfExists('curriculum_objective_skill');
        Schema::dropIfExists('curriculum_skills');
        Schema::dropIfExists('curriculum_indicators');
        Schema::dropIfExists('curriculum_unit_objective');
        Schema::dropIfExists('learning_objectives');
        Schema::dropIfExists('curriculum_units');
        Schema::dropIfExists('curriculum_axes');
        Schema::dropIfExists('curriculum_offerings');
        Schema::dropIfExists('curriculum_subjects');
        Schema::dropIfExists('curriculum_grades');
        Schema::dropIfExists('curriculum_levels');
        Schema::dropIfExists('curriculum_versions');
        Schema::dropIfExists('curriculum_sources');
    }
};
