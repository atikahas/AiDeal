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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('active_api_key_id')
                ->nullable()
                ->after('remember_token')
                ->constrained('api_keys')
                ->nullOnDelete();
        });

        $masterKeyId = DB::table('api_keys')
            ->whereNull('user_id')
            ->where('provider', 'gemini')
            ->where('label', 'Master Gemini Key')
            ->value('id');

        if ($masterKeyId) {
            DB::table('users')
                ->whereNull('active_api_key_id')
                ->update(['active_api_key_id' => $masterKeyId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_api_key_id');
        });
    }
};
