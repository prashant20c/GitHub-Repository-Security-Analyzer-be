<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('commit_hash')->nullable();
            $table->unsignedInteger('security_score')->default(100);
            $table->unsignedInteger('code_quality_score')->default(100);
            $table->unsignedInteger('dependency_score')->default(100);
            $table->unsignedInteger('secret_score')->default(100);
            $table->unsignedInteger('overall_health_score')->default(100);
            $table->unsignedInteger('total_findings')->default(0);
            $table->unsignedInteger('critical_count')->default(0);
            $table->unsignedInteger('high_count')->default(0);
            $table->unsignedInteger('medium_count')->default(0);
            $table->unsignedInteger('low_count')->default(0);
            $table->unsignedInteger('secret_leak_count')->default(0);
            $table->unsignedInteger('dependency_risk_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
