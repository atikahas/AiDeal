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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('label');
            $table->text('secret');
            $table->boolean('is_active')->default(false);
            $table->string('connection_status')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });

        DB::table('api_keys')->insert([
            'provider' => 'gemini',
            'label' => 'Master Gemini Key',
            'secret' => encrypt('your api key'),
            'is_active' => true,
            'connection_status' => 'connected',
            'last_tested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
