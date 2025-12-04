<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('tree_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('description')->nullable();
            $table->integer('timeLimit')->default(30);
            $table->json('settings')->nullable();
            $table->json('grading')->nullable();
            $table->json('correctOrder')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
