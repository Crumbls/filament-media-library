<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mediables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media_library')->cascadeOnDelete();
            $table->morphs('mediable');
            $table->string('collection')->default('default');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['media_id', 'mediable_id', 'mediable_type', 'collection'], 'mediables_unique');
            $table->index(['mediable_type', 'mediable_id', 'collection']);
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediables');
    }
};
