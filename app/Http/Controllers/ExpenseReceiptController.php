<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\ExpenseReceiptService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseReceiptController extends Controller
{
    protected ExpenseReceiptService $receiptService;

    public function __construct(ExpenseReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    /**
     * Stream an authorized expense receipt securely from private storage.
     * Prevents public exposure and enforces role-based access.
     */
    public function viewReceipt(Request $request, $id): BinaryFileResponse
    {
        $expense = Expense::findOrFail($id);

        return $this->receiptService->streamReceipt($expense);
    }
}
