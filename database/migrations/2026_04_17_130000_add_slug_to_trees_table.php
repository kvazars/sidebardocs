<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trees', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        $files = DB::table('trees')
            ->where('type', 'file')
            ->orderBy('id')
            ->get(['id', 'name']);

        foreach ($files as $file) {
            $base = Str::slug((string)$file->name);
            if ($base === '') {
                $base = 'document';
            }

            $slug = $base;
            $suffix = 2;

            while (
                DB::table('trees')
                    ->where('slug', $slug)
                    ->where('id', '!=', $file->id)
                    ->exists()
            ) {
                $slug = $base . '-' . $suffix;
                $suffix++;
            }

            DB::table('trees')
                ->where('id', $file->id)
                ->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trees', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};

