<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('stable_key')->nullable()->after('test_id');
            $table->index('stable_key');
        });

        DB::table('questions')
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function ($question) {
                DB::table('questions')
                    ->where('id', $question->id)
                    ->update(['stable_key' => (string) Str::uuid()]);
            });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['stable_key']);
            $table->dropColumn('stable_key');
        });
    }
};
