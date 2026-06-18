<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->string('tool');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity');
            $table->string('file_path')->nullable();
            $table->integer('line_number')->nullable();
            $table->longText('code_snippet')->nullable();
            $table->string('owasp_category')->nullable();
            $table->string('cwe_id')->nullable();
            $table->unsignedInteger('risk_score')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
    }
};
