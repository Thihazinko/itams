<?php

namespace App\Http\Controllers;

use App\Models\LicenseContract;
use App\Models\LicenseContractAttachment;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LicenseContractAttachmentController extends Controller
{
    /**
     * Attach one or more files (contract, invoice, renewal quote, …) to a
     * license/contract record.
     */
    public function store(Request $request, LicenseContract $licenses_contract)
    {
        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx|max:20480',
            'label'   => 'nullable|string|max:255',
        ], [
            'files.required' => 'Choose at least one file to attach.',
            'files.*.mimes'  => 'Attachments must be a PDF, image, Word, or Excel file.',
            'files.*.max'    => 'Each attachment must be 20 MB or smaller.',
        ]);

        $files = $request->file('files');
        foreach ($files as $file) {
            $licenses_contract->attachments()->create([
                'file_path'     => $file->store('license_contracts', 'public'),
                'original_name' => $file->getClientOriginalName(),
                'label'         => $request->input('label') ?: null,
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
                'uploaded_by'   => $request->user()->name,
            ]);
        }

        $count = count($files);

        ActivityLogger::log(
            action: 'updated',
            description: "Attached {$count} file(s) to license/contract {$licenses_contract->software_name}",
            subject: $licenses_contract,
        );

        return back()->with('success', $count === 1 ? 'Attachment uploaded.' : "{$count} attachments uploaded.");
    }

    /**
     * Download an attachment with a friendly filename.
     */
    public function download(LicenseContract $licenses_contract, LicenseContractAttachment $attachment)
    {
        abort_unless($attachment->license_contract_id === $licenses_contract->id, 404);
        abort_unless(Storage::disk('public')->exists($attachment->file_path), 404);

        $name = $attachment->original_name
            ?: ($licenses_contract->software_name . '-attachment-' . $attachment->id . '.' . pathinfo($attachment->file_path, PATHINFO_EXTENSION));

        return Storage::disk('public')->download($attachment->file_path, $name);
    }

    public function destroy(Request $request, LicenseContract $licenses_contract, LicenseContractAttachment $attachment)
    {
        abort_unless($attachment->license_contract_id === $licenses_contract->id, 404);

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        ActivityLogger::log(
            action: 'updated',
            description: "Removed an attachment from license/contract {$licenses_contract->software_name}",
            subject: $licenses_contract,
        );

        return back()->with('success', 'Attachment deleted.');
    }
}
