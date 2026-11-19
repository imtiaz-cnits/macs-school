<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an admission invoice or head allocation lacks mandatory frozen
 * historical snapshot metadata (branch or fee category names), preventing
 * silent fallback to mutable master data.
 */
class HistoricalSnapshotCorruptException extends RuntimeException
{
}
