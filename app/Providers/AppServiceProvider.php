<?php

namespace App\Providers;

use App\Models\FeePayment;
use App\Services\FeeReceiptService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as IlluminateView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('pages.fees.collection', function (IlluminateView $view): void {
            $data = $view->getData();
            $paymentHistory = $data['paymentHistory'] ?? null;
            if (!$paymentHistory || (method_exists($paymentHistory, 'isEmpty') && $paymentHistory->isEmpty())) {
                return;
            }

            /** @var FeeReceiptService $receiptService */
            $receiptService = app(FeeReceiptService::class);
            $hasAdmSchema = $receiptService->admissionTablesAvailable()
                && Schema::hasColumn('fee_invoices', 'admission_fee_structure_id')
                && Schema::hasColumn('fee_payments', 'status');

            if (!$hasAdmSchema) {
                return;
            }

            foreach ($paymentHistory as $group) {
                foreach ($group as $pay) {
                    if (
                        $pay instanceof FeePayment
                        && !array_key_exists('prepared_alloc_selection', $pay->getAttributes())
                        && $pay->invoice
                        && method_exists($pay->invoice, 'isAdmissionInvoice')
                        && $pay->invoice->isAdmissionInvoice()
                    ) {
                        $pay->setAttribute(
                            'prepared_alloc_selection',
                            $receiptService->selectOrderedPaymentHeadAllocations($pay)
                        );
                    }
                }
            }
        });
    }
}
