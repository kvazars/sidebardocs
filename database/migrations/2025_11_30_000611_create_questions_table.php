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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('text');
            $table->string('type'); // single, multiple, true-false, text, matching
            $table->integer('points')->default(1);
            $table->text('image')->nullable(); // base64 image
            $table->json('options')->nullable(); // for single/multiple choice
            $table->json('correct_answers')->nullable(); // for text questions
            $table->json('pairs')->nullable(); // for matching questions
            $table->string('correct_answer')->nullable(); // for true-false
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
