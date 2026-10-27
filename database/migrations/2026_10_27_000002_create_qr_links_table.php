<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_links', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('qr_page_id')->constrained('qr_pages')->cascadeOnDelete();
            $table->string('token', 32)->unique();
            $table->string('label', 80);
            $table->string('icon_key', 24)->default('website');
            $table->text('destination_url');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('total_clicks')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['qr_page_id', 'sort_order']);
            $table->index(['qr_page_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_links');
    }
};
