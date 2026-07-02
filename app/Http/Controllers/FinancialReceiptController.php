<?php

namespace App\Http\Controllers;

use App\Models\FinancialPo;
use App\Models\FinancialReceipt;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FinancialReceiptController extends Controller
{
    public function store(Request $request, FinancialPo $financialPo)
    {
        $data = $request->validate([
            'receipt_number' => 'nullable|string|max:255',
            'receipt_date'   => 'required|date',
            'paid_amount'    => 'required|numeric|min:0',
            'currency'       => ['required', Rule::in(array_keys(FinancialPo::CURRENCIES))],
            'payment_method' => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
            'file'           => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('receipts', 'public');
        }
        unset($data['file']);

        $data['created_by'] = $request->user()->name;
        $data['modified_by'] = $request->user()->name;

        $receipt = $financialPo->receipts()->create($data);

        ActivityLogger::log(
            action: 'created',
            description: "Recorded receipt for PO {$financialPo->po_number} ({$receipt->currency} " . number_format((float) $receipt->paid_amount, 2) . ')',
            subject: $financialPo,
        );

        return redirect()->route('financial-pos.show', $financialPo)->with('success', 'Receipt recorded.');
    }

    /**
     * Create a receipt from the central Receipts tab, where the Approved PO is
     * chosen from a dropdown (passed as financial_po_id) rather than the URL.
     */
    public function storeFromHistory(Request $request)
    {
        $data = $request->validate([
            'financial_po_id' => 'required|exists:financial_pos,id',
            'receipt_number'  => 'nullable|string|max:255',
            'receipt_date'    => 'required|date',
            'paid_amount'     => 'required|numeric|min:0',
            'currency'        => ['required', Rule::in(array_keys(FinancialPo::CURRENCIES))],
            'payment_method'  => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
            'file'            => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        $financialPo = FinancialPo::findOrFail($data['financial_po_id']);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('receipts', 'public');
        }
        unset($data['file'], $data['financial_po_id']);

        $data['created_by'] = $request->user()->name;
        $data['modified_by'] = $request->user()->name;

        $receipt = $financialPo->receipts()->create($data);

        ActivityLogger::log(
            action: 'created',
            description: "Recorded receipt for PO {$financialPo->po_number} ({$receipt->currency} " . number_format((float) $receipt->paid_amount, 2) . ')',
            subject: $financialPo,
        );

        return redirect()->route('financial-pos.index', ['tab' => 'receipts'])->with('success', 'Receipt uploaded.');
    }

    /**
     * One-click receipt upload from the Approved PO table: just a file. The
     * receipt is created against the PO (and so appears in the Receipts tab
     * automatically), defaulting date to today, amount to the PO's renewal
     * cost, and currency to the PO's currency.
     */
    public function quickUpload(Request $request, FinancialPo $financialPo)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);
        if ($validator->fails()) {
            return back()->with('error', 'Receipt must be a PDF or image (jpg, png, webp) up to 10 MB.');
        }

        $receipt = $financialPo->receipts()->create([
            'receipt_date'   => now()->toDateString(),
            'paid_amount'    => $financialPo->total_amount,
            'currency'       => $financialPo->currency,
            'file_path'      => $request->file('file')->store('receipts', 'public'),
            'created_by'     => $request->user()->name,
            'modified_by'    => $request->user()->name,
        ]);

        ActivityLogger::log(
            action: 'created',
            description: "Uploaded receipt for PO {$financialPo->po_number} ({$receipt->currency} " . number_format((float) $receipt->paid_amount, 2) . ')',
            subject: $financialPo,
        );

        return back()->with('success', 'Receipt uploaded and saved to Receipts.');
    }

    /**
     * Upload (or replace) the attachment on an existing receipt transaction.
     */
    public function uploadFile(Request $request, FinancialPo $financialPo, FinancialReceipt $receipt)
    {
        abort_unless($receipt->financial_po_id === $financialPo->id, 404);

        // This is an inline per-row form with no @error placeholder, so surface a
        // failed validation as a flash message instead of a silent redirect.
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);
        if ($validator->fails()) {
            return back()->with('error', 'Attachment must be a PDF or image (jpg, png, webp) up to 10 MB.');
        }

        // Drop the old file first so replacements don't orphan storage.
        if ($receipt->file_path) {
            Storage::disk('public')->delete($receipt->file_path);
        }

        $receipt->file_path = $request->file('file')->store('receipts', 'public');
        $receipt->modified_by = $request->user()->name;
        $receipt->save();

        ActivityLogger::log(
            action: 'updated',
            description: "Attached a file to a receipt on PO {$financialPo->po_number}",
            subject: $financialPo,
        );

        return back()->with('success', 'Attachment uploaded.');
    }

    /**
     * Download a receipt's attachment with a friendly filename.
     */
    public function downloadFile(Request $request, FinancialPo $financialPo, FinancialReceipt $receipt)
    {
        abort_unless($receipt->financial_po_id === $financialPo->id, 404);
        abort_unless($receipt->file_path && Storage::disk('public')->exists($receipt->file_path), 404);

        $ext = pathinfo($receipt->file_path, PATHINFO_EXTENSION);
        $name = $financialPo->po_number . '-receipt-' . $receipt->id . ($ext ? '.' . $ext : '');

        return Storage::disk('public')->download($receipt->file_path, $name);
    }

    public function destroy(Request $request, FinancialPo $financialPo, FinancialReceipt $receipt)
    {
        abort_unless($receipt->financial_po_id === $financialPo->id, 404);

        if ($receipt->file_path) {
            Storage::disk('public')->delete($receipt->file_path);
        }

        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted receipt from PO {$financialPo->po_number}",
            subject: $financialPo,
        );

        $receipt->delete();

        return redirect()->route('financial-pos.show', $financialPo)->with('success', 'Receipt deleted.');
    }
}
