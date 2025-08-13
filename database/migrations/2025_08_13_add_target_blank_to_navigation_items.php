<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ext_navigation_items', function (Blueprint $table) {
            $table->boolean('target_blank')->default(false)->after('link_value');
        });
    }

    public function down()
    {
        Schema::table('ext_navigation_items', function (Blueprint $table) {
            $table->dropColumn('target_blank');
        });
    }
};
