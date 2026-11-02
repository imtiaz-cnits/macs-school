<?php

namespace App\Services;

use App\Models\BiometricDeviceUser;

class RegistryResolutionResult
{
    public const STATUS_ACTIVE     = 'active';
    public const STATUS_UNRESOLVED   = 'unresolved';
    public const STATUS_RESERVED     = 'reserved';
    public const STATUS_INACTIVE     = 'inactive';
    public const STATUS_RETIRED      = 'retired';
    public const STATUS_MISSING      = 'missing';
    public const STATUS_AMBIGUOUS    = 'ambiguous';

    protected string $status;
    protected ?BiometricDeviceUser $mapping;
    protected mixed $person;
    protected string $reason;

    public function __construct(
        string $status,
        ?BiometricDeviceUser $mapping = null,
        mixed $person = null,
        string $reason = ''
    ) {
        $this->status  = $status;
        $this->mapping = $mapping;
        $this->person  = $person;
        $this->reason  = $reason;
    }

    public static function active(BiometricDeviceUser $mapping, mixed $person): self
    {
        return new self(self::STATUS_ACTIVE, $mapping, $person, "Active mapping confirmed.");
    }

    public static function unresolved(BiometricDeviceUser $mapping, string $reason = ''): self
    {
        return new self(self::STATUS_UNRESOLVED, $mapping, null, $reason ?: "Mapping is registered as unresolved.");
    }

    public static function reserved(BiometricDeviceUser $mapping, string $reason = ''): self
    {
        return new self(self::STATUS_RESERVED, $mapping, null, $reason ?: "Mapping is registered as reserved.");
    }

    public static function inactive(BiometricDeviceUser $mapping, string $reason = ''): self
    {
        return new self(self::STATUS_INACTIVE, $mapping, null, $reason ?: "Mapping is inactive historical record.");
    }

    public static function retired(BiometricDeviceUser $mapping, string $reason = ''): self
    {
        return new self(self::STATUS_RETIRED, $mapping, null, $reason ?: "Mapping is registered as retired.");
    }

    public static function missing(string $reason = ''): self
    {
        return new self(self::STATUS_MISSING, null, null, $reason ?: "No registry record found.");
    }

    public static function ambiguous(string $reason = ''): self
    {
        return new self(self::STATUS_AMBIGUOUS, null, null, $reason ?: "Ambiguous or conflicting records found.");
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->person !== null;
    }

    public function isUnresolved(): bool
    {
        return $this->status === self::STATUS_UNRESOLVED;
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function isRetired(): bool
    {
        return $this->status === self::STATUS_RETIRED;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isMissing(): bool
    {
        return $this->status === self::STATUS_MISSING;
    }

    public function isAmbiguous(): bool
    {
        return $this->status === self::STATUS_AMBIGUOUS;
    }

    /**
     * Critical Rule: Legacy fallback is permitted ONLY if the identifier is completely missing
     * from the registry. Known unresolved, retired, inactive, or reserved profiles must NEVER fall back.
     */
    public function canUseLegacyFallback(): bool
    {
        return $this->status === self::STATUS_MISSING;
    }

    public function mapping(): ?BiometricDeviceUser
    {
        return $this->mapping;
    }

    public function person(): mixed
    {
        return $this->person;
    }

    public function personType(): ?string
    {
        return $this->mapping?->person_type;
    }

    public function personId(): ?int
    {
        return $this->mapping?->person_id !== null ? (int)$this->mapping->person_id : null;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}

