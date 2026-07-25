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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['asset', 'stationery', 'book'])->default('asset');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->text('description')->nullable();
            $table->integer('current_quantity')->default(0);
            $table->string('unit')->default('pcs');
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
