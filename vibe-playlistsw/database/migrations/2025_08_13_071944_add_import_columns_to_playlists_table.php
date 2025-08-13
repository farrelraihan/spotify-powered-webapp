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
        Schema::table('playlists', function (Blueprint $table) {
            $table->unsignedInteger('import_total')->nullable()->after('cover_url');
            $table->unsignedInteger('import_done')->default(0)->after('import_total');
            $table->string('import_status')->nullable()->after('import_done'); // queued, running, done, failed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('playlists', function (Blueprint $table) {
            $table->dropColumn(['import_total', 'import_done', 'import_status']);
        });
    }
};
