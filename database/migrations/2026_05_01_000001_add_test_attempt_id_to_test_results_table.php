<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->unsignedBigInteger('test_attempt_id')
                ->nullable()
                ->after('test_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->dropIndex(['test_attempt_id']);
            $table->dropColumn('test_attempt_id');
        });
    }
};
