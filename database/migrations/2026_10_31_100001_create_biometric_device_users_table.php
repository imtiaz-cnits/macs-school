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
        Schema::create('biometric_device_users', function (Blueprint $table) {
            $table->id();

            // Device Identification (supports multi-device topology)
            $table->string('device_id', 50)->default('k60_main')->index();

            // Hardware Slot & Protocol Identifiers
            $table->unsignedInteger('device_uid'); // Internal ZK sequence index (1..65535)
            $table->string('device_userid', 50);   // Display User ID / PIN on terminal

            // Polymorphic Identity (Student, Teacher, Staff)
            // Nullable to cleanly accommodate unresolved or hardware-reserved identities without synthetic ID 0
            $table->string('person_type', 20)->nullable();
            $table->unsignedBigInteger('person_id')->nullable();

            // Credential Metadata
            $table->string('card_number', 50)->nullable()->index();
            $table->boolean('has_fingerprint')->default(false);
            $table->unsignedTinyInteger('fingerprint_count')->default(0);

            // Biometric Security & Cryptographic Integrity
            $table->string('biometric_checksum', 64)->nullable(); // SHA-256 of verified templates
            $table->longText('encrypted_biometric_data')->nullable(); // Encrypted at application layer via AES-256

            // Lifecycle & Verification State
            $table->enum('status', ['active', 'inactive', 'reserved', 'retired', 'unresolved'])->default('active');
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // 1. Hardware Slot Uniqueness: No two records on the same device can share the same device_uid
            $table->unique(['device_id', 'device_uid'], 'uk_device_uid');

            // 2. Active Person Uniqueness: A person can have only ONE active slot per device.
            // When status != 'active', active_person_id evaluates to NULL (multiple NULLs are permitted in UNIQUE index).
            $table->unsignedBigInteger('active_person_id')->virtualAs("CASE WHEN status = 'active' THEN person_id ELSE NULL END");
            $table->unique(['device_id', 'person_type', 'active_person_id'], 'uk_active_person_device');

            // 3. Active Terminal PIN Uniqueness: Prevent two active records on the same terminal from sharing a PIN.
            $table->string('active_device_userid', 50)->nullable()->virtualAs("CASE WHEN status = 'active' THEN device_userid ELSE NULL END");
            $table->unique(['device_id', 'active_device_userid'], 'uk_active_device_userid');

            // 4. Active RFID Card Uniqueness: Prevent two active people from being assigned the same RFID card.
            $table->string('active_card_number', 50)->nullable()->virtualAs("CASE WHEN status = 'active' AND card_number IS NOT NULL AND card_number != '' THEN card_number ELSE NULL END");
            $table->unique(['device_id', 'active_card_number'], 'uk_active_card_number');

            // Indexes for fast lookup
            $table->index(['person_type', 'person_id'], 'idx_person');
            $table->index(['device_id', 'status'], 'idx_device_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_device_users');
    }
};

