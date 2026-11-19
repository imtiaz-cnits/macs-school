<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_daily_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('scope_key', 80)->unique('uq_fds_scope_key');
            $table->unsignedBigInteger('last_seq')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_daily_sequences');
    }
};
