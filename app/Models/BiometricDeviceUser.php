<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BiometricDeviceUser extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Attribute casts for data types and cryptographic security.
     */
    protected $casts = [
        'device_uid'               => 'integer',
        'has_fingerprint'          => 'boolean',
        'fingerprint_count'        => 'integer',
        'verified_at'              => 'datetime',
        'encrypted_biometric_data' => 'encrypted', // Automatic AES-256 encryption via Laravel APP_KEY
    ];

    /**
     * Resolve the associated person model (Student or Teacher).
     * Returns null if person_id is null (unresolved/reserved profile).
     */
    public function person()
    {
        if ($this->person_type === 'student') {
            return $this->belongsTo(Student::class, 'person_id');
        }

        if ($this->person_type === 'teacher') {
            return $this->belongsTo(Teacher::class, 'person_id');
        }

        return $this->belongsTo(Teacher::class, 'person_id');
    }

    /**
     * Scope for active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for unresolved records.
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('status', 'unresolved');
    }

    /**
     * Scope for reserved records.
     */
    public function scopeReserved(Builder $query): Builder
    {
        return $query->where('status', 'reserved');
    }

    /**
     * Scope for a specific hardware terminal.
     */
    public function scopeForDevice(Builder $query, string $deviceId = 'k60_main'): Builder
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Scope by hardware slot UID.
     */
    public function scopeForUid(Builder $query, int $uid, string $deviceId = 'k60_main'): Builder
    {
        return $query->where('device_id', $deviceId)->where('device_uid', $uid);
    }

    /**
     * Scope for a specific person.
     */
    public function scopeForPerson(Builder $query, string $personType, int $personId, string $deviceId = 'k60_main'): Builder
    {
        return $query->where('device_id', $deviceId)
                     ->where('person_type', $personType)
                     ->where('person_id', $personId);
    }
}

