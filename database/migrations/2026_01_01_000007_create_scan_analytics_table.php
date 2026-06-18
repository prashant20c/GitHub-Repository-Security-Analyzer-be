<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scan_analytics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('scan_number')->default(1);
            $table->unsignedInteger('security_score')->default(100);
            $table->unsignedInteger('code_quality_score')->default(100);
            $table->unsignedInteger('dependency_score')->default(100);
            $table->unsignedInteger('secret_score')->default(100);
            $table->unsignedInteger('overall_health_score')->default(100);
            $table->unsignedInteger('total_vulnerabilities')->default(0);
            $table->unsignedInteger('secret_leak_count')->default(0);
            $table->unsignedInteger('dependency_risk_count')->default(0);
            $table->string('risk_level');
            $table->string('trend_direction');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_analytics');
    }
};
