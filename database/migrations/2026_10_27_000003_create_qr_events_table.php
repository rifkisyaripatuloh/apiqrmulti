<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('qr_page_id')->constrained('qr_pages')->cascadeOnDelete();
            $table->foreignUlid('qr_link_id')->nullable()->constrained('qr_links')->nullOnDelete();
            $table->string('type', 8);
            $table->char('visitor_key', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['qr_page_id', 'type', 'created_at']);
            $table->index(['qr_link_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_events');
    }
};
