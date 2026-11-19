<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('scoped_key', 64)->unique('uq_fik_scoped_key');
            $table->string('operation', 50);
            $table->unsignedBigInteger('actor_user_id')->index('idx_fik_actor');
            $table->string('target_scope', 255);
            $table->string('idempotency_key', 120);
            $table->text('metadata_json')->nullable();
            $table->dateTime('expires_at')->index('idx_fik_expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_idempotency_keys');
    }
};
