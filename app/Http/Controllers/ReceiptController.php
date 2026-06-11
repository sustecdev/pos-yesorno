<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptController extends Controller
{
    public function show(Receipt $receipt)
    {
        $data = $receipt->data;

        return Pdf::loadView('receipts.pdf', compact('data', 'receipt'))
            ->stream("receipt-{$receipt->receipt_number}.pdf");
    }
}
