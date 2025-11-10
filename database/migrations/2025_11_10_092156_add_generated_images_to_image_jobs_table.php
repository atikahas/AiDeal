<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('image_jobs', function (Blueprint $table) {
            $table->json('generated_images')->nullable()->after('result_image_path');
            $table->boolean('is_saved')->default(false)->after('status');
        });
    }

    public function down()
    {
        Schema::table('image_jobs', function (Blueprint $table) {
            $table->dropColumn(['generated_images', 'is_saved']);
        });
    }
};