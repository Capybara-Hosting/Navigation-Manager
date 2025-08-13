<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ext_navigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Display name of the navigation item
            $table->string('link_type'); // 'route', 'url', 'custom'
            $table->string('link_value'); // Route name, URL, or custom path
            $table->json('route_params')->nullable(); // JSON encoded route parameters
            $table->string('icon')->nullable(); // Icon class (e.g., 'ri-home-line')
            $table->string('location')->default('main'); // 'main', 'account_dropdown', 'dashboard'
            $table->string('visibility')->default('public'); // 'public', 'logged_in', 'guest', 'role'
            $table->json('allowed_roles')->nullable(); // JSON array of role IDs when visibility is 'role'
            $table->unsignedBigInteger('parent_id')->nullable(); // For dropdown/nested items
            $table->integer('sort_order')->default(0); // Order of items
            $table->boolean('is_enabled')->default(true); // Enable/disable the item
            $table->text('description')->nullable(); // Optional description
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('ext_navigation_items')->onDelete('cascade');
            $table->index(['location', 'is_enabled', 'sort_order']);
            $table->index(['parent_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ext_navigation_items');
    }
};
