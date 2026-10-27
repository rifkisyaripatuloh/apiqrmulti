<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_pages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 16)->unique();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('status', 16)->default('draft');
            $table->unsignedBigInteger('total_scans')->default(0);
            $table->unsignedBigInteger('total_clicks')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_pages');
    }
};
