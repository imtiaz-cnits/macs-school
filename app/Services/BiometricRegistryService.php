<?php

namespace App\Services;

use App\Models\BiometricDeviceUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BiometricRegistryService
{
    /**
     * Default Device UID Ranges
     */
    public const STUDENT_UID_MIN = 31;
    public const STUDENT_UID_MAX = 8999;

    public const STAFF_UID_MIN = 9000;
    public const STAFF_UID_MAX = 65535;

    public const DEVICE_UID_LIMIT = 65535;

    /**
     * Permanent Hardware Reservations (Phase 3C Audit findings)
     * These UIDs are permanently safeguarded against automatic reassignment.
     * Note: Unresolved identities use 'person_id' => null to avoid synthetic relationships.
     */
    public const RESERVED_UIDS = [
        569   => ['person_type' => 'teacher', 'person_id' => 5,    'status' => 'active',     'name' => 'Imtiaz Ahmed (Teacher #5)'],
        10006 => ['person_type' => 'teacher', 'person_id' => 6,    'status' => 'active',     'name' => 'Md Mahmudul Hasan (Teacher #6)'],
        10008 => ['person_type' => 'teacher', 'person_id' => 8,    'status' => 'active',     'name' => 'FAHIM AHMAD ZAMIL (Teacher #8)'],
        10010 => ['person_type' => 'teacher', 'person_id' => 10,   'status' => 'active',     'name' => 'Md. Nurul Islam (Teacher #10)'],
        10025 => ['person_type' => 'staff',   'person_id' => null, 'status' => 'unresolved', 'name' => 'Fahim (Legacy PIN 21 - Unresolved)'],
        217   => ['person_type' => 'student', 'person_id' => null, 'status' => 'retired',    'name' => 'Dreamy faith symbol (Retired Test Profile)'],
        218   => ['person_type' => 'student', 'person_id' => null, 'status' => 'retired',    'name' => 'Ahnaf Islam Abir (Retired Test Profile)'],
    ];

    /**
     * Allocate a collision-free Device UID for a person on a specific terminal.
     * If an active mapping already exists for this person, returns the existing UID.
     */
    public function allocateNextUid(string $personType, ?int $personId = null, string $deviceId = 'k60_main'): int
    {
        // 1. Check if person already has an active UID allocation on this device
        if ($personId !== null) {
            $existing = BiometricDeviceUser::query()
                ->forDevice($deviceId)
                ->forPerson($personType, $personId)
                ->active()
                ->first();

            if ($existing) {
                return (int) $existing->device_uid;
            }

            // 2. Check if person owns a dedicated reservation
            foreach (self::RESERVED_UIDS as $resUid => $resMeta) {
                if ($resMeta['person_type'] === $personType && $resMeta['person_id'] !== null && (int)$resMeta['person_id'] === $personId) {
                    if ($this->isUidAvailable($deviceId, $resUid)) {
                        return $resUid;
                    }
                }
            }
        }

        // 3. Determine range based on person type
        $min = ($personType === 'student') ? self::STUDENT_UID_MIN : self::STAFF_UID_MIN;
        $max = ($personType === 'student') ? self::STUDENT_UID_MAX : self::STAFF_UID_MAX;

        // Fetch all UIDs currently in use on this device
        $usedUids = BiometricDeviceUser::query()
            ->forDevice($deviceId)
            ->pluck('device_uid')
            ->map(fn($id) => (int)$id)
            ->flip()
            ->toArray();

        // Find the lowest available free integer
        for ($candidate = $min; $candidate <= $max; $candidate++) {
            // Check hardcoded reservations
            if (isset(self::RESERVED_UIDS[$candidate])) {
                $res = self::RESERVED_UIDS[$candidate];
                // Only allow candidate if explicitly reserved for THIS requesting person
                if ($personId === null || !($res['person_type'] === $personType && $res['person_id'] !== null && (int)$res['person_id'] === $personId)) {
                    continue; // Skip reserved for another entity or unresolved
                }
            }

            // Check database registry
            if (!isset($usedUids[$candidate])) {
                return $candidate;
            }
        }

        throw new RuntimeException("Biometric device UID capacity exhausted for {$personType} on device '{$deviceId}' (Range {$min}-{$max}).");
    }

    /**
     * Check if a specific UID is available on a device.
     */
    public function isUidAvailable(string $deviceId, int $uid, ?int $excludeMappingId = null): bool
    {
        if ($uid < 1 || $uid > self::DEVICE_UID_LIMIT) {
            return false;
        }

        $query = BiometricDeviceUser::query()
            ->forDevice($deviceId)
            ->where('device_uid', $uid);

        if ($excludeMappingId) {
            $query->where('id', '!=', $excludeMappingId);
        }

        return !$query->exists();
    }

    /**
     * Register a new active biometric device user mapping.
     */
    public function registerUser(array $data): BiometricDeviceUser
    {
        $deviceId   = $data['device_id'] ?? 'k60_main';
        $personType = $data['person_type'] ?? null;
        $personId   = isset($data['person_id']) && $data['person_id'] !== null ? (int) $data['person_id'] : null;
        $deviceUid  = (int) $data['device_uid'];
        $userId     = (string) ($data['device_userid'] ?? $deviceUid);

        // Enforce range and hardware ceiling
        if ($deviceUid < 1 || $deviceUid > self::DEVICE_UID_LIMIT) {
            throw ValidationException::withMessages([
                'device_uid' => "Device UID must be between 1 and " . self::DEVICE_UID_LIMIT . "."
            ]);
        }

        $status     = $data['status'] ?? 'active';

        // Check if an active mapping already exists for this person on this device
        if ($personId !== null && $status === 'active') {
            $activeExists = BiometricDeviceUser::query()
                ->forDevice($deviceId)
                ->forPerson($personType, $personId)
                ->active()
                ->exists();

            if ($activeExists) {
                throw ValidationException::withMessages([
                    'person_id' => "An active device mapping already exists for {$personType} ID {$personId} on device '{$deviceId}'."
                ]);
            }
        }

        // Check if UID is already in use on this device
        if (!$this->isUidAvailable($deviceId, $deviceUid)) {
            throw ValidationException::withMessages([
                'device_uid' => "Device UID {$deviceUid} is already occupied or reserved on device '{$deviceId}'."
            ]);
        }

        // Enforce active PIN uniqueness on terminal
        if ($status === 'active') {
            $pinInUse = BiometricDeviceUser::query()
                ->forDevice($deviceId)
                ->where('device_userid', $userId)
                ->active()
                ->exists();

            if ($pinInUse) {
                throw ValidationException::withMessages([
                    'device_userid' => "Terminal PIN '{$userId}' is already occupied by an active profile on device '{$deviceId}'."
                ]);
            }

            // Enforce active Card uniqueness on terminal
            $cardNumber = !empty($data['card_number']) ? trim($data['card_number']) : null;
            if ($cardNumber && $cardNumber !== '0' && $cardNumber !== '0000000000') {
                $cardInUse = BiometricDeviceUser::query()
                    ->forDevice($deviceId)
                    ->where('card_number', $cardNumber)
                    ->active()
                    ->exists();

                if ($cardInUse) {
                    throw ValidationException::withMessages([
                        'card_number' => "Card number '{$cardNumber}' is already assigned to an active profile on device '{$deviceId}'."
                    ]);
                }
            }
        }

        // Biometric template checksum calculation if raw templates provided
        $checksum = $data['biometric_checksum'] ?? null;
        if (!empty($data['raw_template_data']) && empty($checksum)) {
            $checksum = hash('sha256', (string)$data['raw_template_data']);
        }

        return BiometricDeviceUser::create([
            'device_id'                => $deviceId,
            'device_uid'               => $deviceUid,
            'device_userid'            => $userId,
            'person_type'              => $personType,
            'person_id'                => $personId,
            'card_number'              => $data['card_number'] ?? null,
            'has_fingerprint'          => (bool) ($data['has_fingerprint'] ?? false),
            'fingerprint_count'        => (int) ($data['fingerprint_count'] ?? 0),
            'biometric_checksum'       => $checksum,
            'encrypted_biometric_data' => $data['encrypted_biometric_data'] ?? null,
            'status'                   => $data['status'] ?? 'active',
            'verified_at'              => $data['verified_at'] ?? null,
            'notes'                    => $data['notes'] ?? null,
            'created_by'               => $data['created_by'] ?? null,
        ]);
    }

    /**
     * Concurrency-safe atomic allocation and registration.
     * Uses database transactions, row locking, and collision retry logic.
     * The retry loop executes outside the transaction wrapper so each retry starts
     * a fresh database transaction with a clean, updated snapshot.
     */
    public function allocateAndRegisterUser(array $data, int $maxRetries = 5): BiometricDeviceUser
    {
        $deviceId   = $data['device_id'] ?? 'k60_main';
        $personType = $data['person_type'] ?? 'student';
        $personId   = isset($data['person_id']) && $data['person_id'] !== null ? (int)$data['person_id'] : null;

        $attempts = 0;
        $lastException = null;

        while ($attempts < $maxRetries) {
            $attempts++;

            try {
                return DB::transaction(function () use ($data, $deviceId, $personType, $personId) {
                    // Pessimistic lock on supporting database engines to order concurrent allocators
                    try {
                        BiometricDeviceUser::query()->forDevice($deviceId)->lockForUpdate()->count();
                    } catch (\Throwable) {
                        // SQLite handles locks at transaction level
                    }

                    $candidateUid = $this->allocateNextUid($personType, $personId, $deviceId);

                    $payload = array_merge($data, [
                        'device_id'     => $deviceId,
                        'device_uid'    => $candidateUid,
                        'device_userid' => (string) ($data['device_userid'] ?? $candidateUid),
                    ]);

                    return $this->registerUser($payload);
                });
            } catch (\Throwable $e) {
                $lastException = $e;

                if ($this->isRetryableCollisionException($e)) {
                    if ($attempts < $maxRetries) {
                        // Backoff with slight jitter to prevent thundering herd under high concurrency
                        usleep(random_int(5000, 25000)); // 5ms - 25ms
                        continue;
                    }
                    // Max attempts exhausted for retryable collision: break to throw controlled RuntimeException
                    break;
                }

                // If exception is not retryable, propagate immediately
                throw $e;
            }
        }

        throw new RuntimeException(
            "Failed to allocate a collision-free UID after {$maxRetries} concurrent attempts.",
            0,
            $lastException
        );
    }

    /**
     * Determine if an exception represents a transient slot collision, deadlock, or lock timeout
     * that can be safely retried in a fresh transaction.
     */
    public function isRetryableCollisionException(\Throwable $e): bool
    {
        // 1. ValidationException specifically for device_uid slot reservation/occupancy
        if ($e instanceof ValidationException) {
            $errors = $e->errors();
            return isset($errors['device_uid']) && count($errors) === 1;
        }

        // 2. Laravel DeadlockException (wrapped by DB::transaction)
        if ($e instanceof \Illuminate\Database\DeadlockException) {
            return true;
        }

        // 3. Database exceptions (UniqueConstraintViolationException or QueryException)
        if ($e instanceof \Illuminate\Database\QueryException) {
            $message = $e->getMessage();
            $sqlState = (string) $e->getCode();
            $previous = $e->getPrevious();
            $errorCode = ($previous instanceof \PDOException && isset($previous->errorInfo[1]))
                ? (int) $previous->errorInfo[1]
                : null;

            // MySQL 1213: Deadlock found when trying to get lock; try restarting transaction (SQLSTATE 40001)
            if ($errorCode === 1213 || $sqlState === '40001' || stripos($message, 'deadlock') !== false) {
                return true;
            }

            // MySQL 1205: Lock wait timeout exceeded; try restarting transaction (SQLSTATE HY000)
            if ($errorCode === 1205 || stripos($message, 'lock wait timeout') !== false) {
                return true;
            }

            // SQLite database locked / busy
            if (stripos($message, 'database is locked') !== false || stripos($message, 'database table is locked') !== false) {
                return true;
            }

            // MySQL 1062 / Duplicate entry specifically on uk_device_uid
            if (str_contains($message, 'uk_device_uid') ||
                str_contains($message, 'UNIQUE constraint failed: biometric_device_users.device_id, biometric_device_users.device_uid')) {
                return true;
            }

            // Generic duplicate entry ONLY if it is on device_uid, not active_person or card_number
            if (stripos($message, 'duplicate entry') !== false &&
                !str_contains($message, 'uk_active_person_device') &&
                !str_contains($message, 'uk_active_card_number') &&
                !str_contains($message, 'card_number')) {
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * Check if exception represents a unique slot collision on uk_device_uid.
     * Backward-compatible alias for isRetryableCollisionException.
     */
    protected function isSlotCollisionException(\Throwable $e): bool
    {
        return $this->isRetryableCollisionException($e);
    }

    /**
     * Safely deactivate an active mapping without destroying historical records.
     */
    public function deactivateMapping(BiometricDeviceUser $mapping, string $reason = ''): bool
    {
        $notes = trim($mapping->notes . "\nDeactivated at " . now()->toDateTimeString() . ($reason ? ": {$reason}" : ''));
        return $mapping->update([
            'status' => 'inactive',
            'notes'  => $notes,
        ]);
    }

    /**
     * Retire a mapping permanently (locks the UID while freeing the person slot).
     */
    public function retireMapping(BiometricDeviceUser $mapping, string $reason = ''): bool
    {
        $notes = trim($mapping->notes . "\nRetired at " . now()->toDateTimeString() . ($reason ? ": {$reason}" : ''));
        return $mapping->update([
            'status' => 'retired',
            'notes'  => $notes,
        ]);
    }

    /**
     * Resolve person by physical device UID.
     */
    /**
     * Resolve person by physical device UID.
     */
    public function resolvePersonByUid(string $deviceId, int $uid): ?BiometricDeviceUser
    {
        return BiometricDeviceUser::query()
            ->forDevice($deviceId)
            ->forUid($uid, $deviceId)
            ->active()
            ->first();
    }

    /**
     * Resolve person by physical terminal PIN (device_userid).
     * Returns an explicit RegistryResolutionResult distinguishing active, unresolved, reserved,
     * inactive, retired, missing, and ambiguous mappings.
     */
    public function resolvePersonByPin(string $deviceId, string $pin): RegistryResolutionResult
    {
        $pin = trim((string)$pin);
        if ($pin === '') {
            return RegistryResolutionResult::missing("Empty PIN provided.");
        }

        $records = BiometricDeviceUser::query()
            ->forDevice($deviceId)
            ->where('device_userid', $pin)
            ->get();

        if ($records->isEmpty()) {
            return RegistryResolutionResult::missing("No registry record found for PIN '{$pin}' on device '{$deviceId}'.");
        }

        $activeRecords = $records->where('status', 'active');

        if ($activeRecords->count() > 1) {
            return RegistryResolutionResult::ambiguous("Multiple active records found for PIN '{$pin}' on device '{$deviceId}'.");
        }

        if ($activeRecords->count() === 1) {
            $active = $activeRecords->first();
            if ($active->person_id === null) {
                return RegistryResolutionResult::unresolved($active, "Active record has unassigned person_id.");
            }
            $person = $active->person;
            if (!$person) {
                return RegistryResolutionResult::unresolved($active, "Associated {$active->person_type} record ID {$active->person_id} not found in database.");
            }
            return RegistryResolutionResult::active($active, $person);
        }

        // No active record; inspect non-active records in strict precedence order
        $unresolved = $records->firstWhere('status', 'unresolved');
        if ($unresolved) {
            return RegistryResolutionResult::unresolved($unresolved, "PIN '{$pin}' is registered as unresolved.");
        }

        $reserved = $records->firstWhere('status', 'reserved');
        if ($reserved) {
            return RegistryResolutionResult::reserved($reserved, "PIN '{$pin}' is registered as reserved.");
        }

        $retired = $records->firstWhere('status', 'retired');
        if ($retired) {
            return RegistryResolutionResult::retired($retired, "PIN '{$pin}' is registered as retired.");
        }

        $inactive = $records->firstWhere('status', 'inactive');
        if ($inactive) {
            return RegistryResolutionResult::inactive($inactive, "PIN '{$pin}' belongs to an inactive historical mapping.");
        }

        return RegistryResolutionResult::ambiguous("Conflicting non-active records found for PIN '{$pin}'.");
    }

    /**
     * Resolve person by physical RFID card number.
     * Returns an explicit RegistryResolutionResult.
     */
    public function resolvePersonByCard(string $deviceId, string $cardNumber): RegistryResolutionResult
    {
        $cardNumber = trim((string)$cardNumber);
        if ($cardNumber === '' || $cardNumber === '0' || $cardNumber === '0000000000') {
            return RegistryResolutionResult::missing("Empty or invalid card number provided.");
        }

        $records = BiometricDeviceUser::query()
            ->forDevice($deviceId)
            ->where('card_number', $cardNumber)
            ->get();

        if ($records->isEmpty()) {
            return RegistryResolutionResult::missing("No registry record found for Card '{$cardNumber}' on device '{$deviceId}'.");
        }

        $activeRecords = $records->where('status', 'active');

        if ($activeRecords->count() > 1) {
            return RegistryResolutionResult::ambiguous("Multiple active records found for Card '{$cardNumber}' on device '{$deviceId}'.");
        }

        if ($activeRecords->count() === 1) {
            $active = $activeRecords->first();
            if ($active->person_id === null) {
                return RegistryResolutionResult::unresolved($active, "Active card mapping has unassigned person_id.");
            }
            $person = $active->person;
            if (!$person) {
                return RegistryResolutionResult::unresolved($active, "Associated {$active->person_type} record ID {$active->person_id} not found in database.");
            }
            return RegistryResolutionResult::active($active, $person);
        }

        $unresolved = $records->firstWhere('status', 'unresolved');
        if ($unresolved) {
            return RegistryResolutionResult::unresolved($unresolved, "Card '{$cardNumber}' is registered as unresolved.");
        }

        $reserved = $records->firstWhere('status', 'reserved');
        if ($reserved) {
            return RegistryResolutionResult::reserved($reserved, "Card '{$cardNumber}' is registered as reserved.");
        }

        $retired = $records->firstWhere('status', 'retired');
        if ($retired) {
            return RegistryResolutionResult::retired($retired, "Card '{$cardNumber}' is registered as retired.");
        }

        $inactive = $records->firstWhere('status', 'inactive');
        if ($inactive) {
            return RegistryResolutionResult::inactive($inactive, "Card '{$cardNumber}' belongs to an inactive historical mapping.");
        }

        return RegistryResolutionResult::ambiguous("Conflicting non-active records found for Card '{$cardNumber}'.");
    }
}

