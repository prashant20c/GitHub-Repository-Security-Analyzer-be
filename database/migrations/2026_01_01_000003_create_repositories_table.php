<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('owner');
            $table->string('url');
            $table->string('default_branch')->default('main');
            $table->string('scan_frequency')->default('manual')->index();
            $table->boolean('is_scheduled')->default(false);
            $table->timestamp('next_scan_at')->nullable()->index();
            $table->timestamp('last_scan_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'owner', 'name']);
            $table->index(['user_id', 'is_scheduled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
