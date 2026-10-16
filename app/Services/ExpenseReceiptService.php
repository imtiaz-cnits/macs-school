<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseReceiptService
{
    /**
     * Allowed MIME types for expense receipts and vouchers.
     */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    /**
     * Allowed file extensions.
     */
    public const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'pdf',
    ];

    /**
     * Maximum allowed file size in bytes (5 MB).
     */
    public const MAX_SIZE_BYTES = 5 * 1024 * 1024; // 5,242,880 bytes

    /**
     * Private storage disk.
     */
    public const STORAGE_DISK = 'local';

    /**
     * Subdirectory under private storage.
     */
    public const STORAGE_DIR = 'expense_receipts';

    /**
     * Validate an uploaded receipt file against strict MIME type, extension, and size limits.
     *
     * @throws ValidationException
     */
    public function validateFile(UploadedFile $file): void
    {
        // 1. Check file size
        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw ValidationException::withMessages([
                'receipt' => ['The receipt file may not be greater than 5 MB.'],
            ]);
        }

        // 2. Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'receipt' => ['The receipt must be a file of type: jpg, jpeg, png, webp, pdf.'],
            ]);
        }

        // 3. Check actual inspected MIME type (not trusting user-supplied header)
        $detectedMime = $file->getMimeType();
        if (!in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'receipt' => ['The file content does not match an allowed format (JPEG, PNG, WebP, or PDF).'],
            ]);
        }
    }

    /**
     * Store an uploaded receipt file into private storage with a cryptographically randomized filename.
     * Prevents path traversal and never exposes the file to the public disk.
     *
     * @return string Stored relative path (e.g. 'expense_receipts/aB3...xyz.pdf')
     * @throws ValidationException
     */
    public function storeReceipt(UploadedFile $file): string
    {
        $this->validateFile($file);

        // Sanitize and normalize extension based on inspected mime type
        $extension = strtolower($file->getClientOriginalExtension());
        if (empty($extension)) {
            $extension = $file->guessExtension() ?? 'bin';
        }

        // Generate a 40-character randomized filename to prevent any path traversal or predictability
        $randomName = Str::random(40) . '.' . $extension;

        // Store into private storage
        $storedPath = Storage::disk(self::STORAGE_DISK)->putFileAs(
            self::STORAGE_DIR,
            $file,
            $randomName
        );

        if (!$storedPath) {
            throw new \RuntimeException('Failed to store receipt in private storage.');
        }

        return $storedPath;
    }

    /**
     * Safely delete a receipt file from private storage.
     */
    public function deleteReceiptFile(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        // Clean and prevent path traversal
        $normalized = str_replace(['..', '\\'], ['', '/'], $path);

        if (Storage::disk(self::STORAGE_DISK)->exists($normalized)) {
            return Storage::disk(self::STORAGE_DISK)->delete($normalized);
        }

        return false;
    }

    /**
     * Stream an authorized receipt file directly to the client.
     * Ensures private files are served inline with appropriate Content-Type headers.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function streamReceipt(Expense $expense): BinaryFileResponse
    {
        if (empty($expense->receipt_file)) {
            abort(404, 'No receipt file is attached to this expense.');
        }

        $normalized = str_replace(['..', '\\'], ['', '/'], $expense->receipt_file);

        if (!Storage::disk(self::STORAGE_DISK)->exists($normalized)) {
            abort(404, 'Receipt file could not be found in storage.');
        }

        $fullPath = Storage::disk(self::STORAGE_DISK)->path($normalized);
        $mime = Storage::disk(self::STORAGE_DISK)->mimeType($normalized) ?? 'application/octet-stream';

        $filename = "Voucher-{$expense->voucher_no}." . pathinfo($fullPath, PATHINFO_EXTENSION);

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
