<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            if (! Schema::hasColumn('api_keys', 'connection_status')) {
                $table->string('connection_status')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('api_keys', 'last_tested_at')) {
                $table->timestamp('last_tested_at')->nullable()->after('connection_status');
            }
        });

        DB::table('api_keys')
            ->whereNull('connection_status')
            ->update([
                'connection_status' => 'unknown',
            ]);

        DB::table('api_keys')
            ->whereNull('user_id')
            ->where('provider', 'gemini')
            ->update([
                'connection_status' => 'connected',
                'last_tested_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            if (Schema::hasColumn('api_keys', 'last_tested_at')) {
                $table->dropColumn('last_tested_at');
            }

            if (Schema::hasColumn('api_keys', 'connection_status')) {
                $table->dropColumn('connection_status');
            }
        });
    }
};
