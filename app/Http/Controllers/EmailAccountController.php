<?php

namespace App\Http\Controllers;

use App\Exports\EmailAccountsExport;
use App\Exports\EmailAccountsTemplate;
use App\Imports\EmailAccountsImport;
use App\Models\EmailAccount;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class EmailAccountController extends Controller
{
    public function create(Request $request)
    {
        $type = in_array($request->get('type'), EmailAccount::TYPES, true)
            ? $request->get('type')
            : 'Gmail';

        return view('email_master.accounts.create', compact('type'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $account = EmailAccount::create($data);

        ActivityLogger::log(
            action: 'created',
            description: "Created {$account->type} account {$account->address}",
            subject: $account,
            properties: ['type' => $account->type],
        );

        return redirect()
            ->route('email-master.index', ['tab' => $this->tabFor($account->type)])
            ->with('success', "{$account->type} account created.");
    }

    public function edit(EmailAccount $emailAccount)
    {
        return view('email_master.accounts.edit', ['account' => $emailAccount]);
    }

    public function update(Request $request, EmailAccount $emailAccount)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $emailAccount->update($data);

        ActivityLogger::log(
            action: 'updated',
            description: "Updated {$emailAccount->type} account {$emailAccount->address}",
            subject: $emailAccount,
            properties: ['type' => $emailAccount->type],
        );

        return redirect()
            ->route('email-master.index', ['tab' => $this->tabFor($emailAccount->type)])
            ->with('success', "{$emailAccount->type} account updated.");
    }

    public function destroy(EmailAccount $emailAccount)
    {
        $type = $emailAccount->type;

        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted {$type} account {$emailAccount->address}",
            subject: $emailAccount,
            properties: ['type' => $type],
        );

        $emailAccount->delete();

        return redirect()
            ->route('email-master.index', ['tab' => $this->tabFor($type)])
            ->with('success', "{$type} account deleted.");
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:email_accounts,id',
        ]);

        $accounts = EmailAccount::whereIn('id', $data['ids'])->get();
        $tab = $accounts->first() ? $this->tabFor($accounts->first()->type) : 'gmail';

        foreach ($accounts as $account) {
            ActivityLogger::log(
                action: 'deleted',
                description: "Deleted {$account->type} account {$account->address} [bulk]",
                subject: $account,
                properties: ['type' => $account->type],
            );
            $account->delete();
        }

        $count = $accounts->count();

        return redirect()
            ->route('email-master.index', ['tab' => $tab])
            ->with('success', "Deleted {$count} account(s).");
    }

    public function export(Request $request)
    {
        $type = in_array($request->get('type'), EmailAccount::TYPES, true) ? $request->get('type') : null;
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';
        $slug = $type ? strtolower($type) : 'accounts';

        return Excel::download(new EmailAccountsExport($type), "email-{$slug}-" . now()->format('Ymd-His') . '.' . $ext, $format);
    }

    public function template(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new EmailAccountsTemplate(), 'email-accounts-template.' . $ext, $format);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'type' => ['nullable', Rule::in(EmailAccount::TYPES)],
        ]);

        $defaultType = in_array($request->get('type'), EmailAccount::TYPES, true) ? $request->get('type') : 'Email';

        $import = new EmailAccountsImport($defaultType);
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        $imported = $import->imported;
        $failed = count($import->failures);

        ActivityLogger::log(
            action: 'imported',
            description: "Imported {$imported} email account(s)" . ($failed > 0 ? " ({$failed} failed)" : ''),
            properties: ['type' => $defaultType, 'imported' => $imported, 'failed' => $failed],
        );

        if ($failed > 0) {
            $msg = "Imported {$imported} new row(s); {$failed} row(s) failed validation.";
            $details = collect($import->failures)
                ->take(10)
                ->map(fn ($f) => 'Row ' . ($f['row'] ?? '?') . ' (' . $f['attribute'] . '): ' . implode(', ', $f['errors']))
                ->implode(' | ');
            return back()->with('error', $msg . ' ' . $details);
        }

        return back()->with('success', "Imported {$imported} account(s) successfully.");
    }

    private function tabFor(string $type): string
    {
        return $type === 'Gmail' ? 'gmail' : 'email';
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'type'       => ['required', Rule::in(EmailAccount::TYPES)],
            'status'     => ['required', Rule::in(EmailAccount::STATUSES)],
            'name'       => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'address'    => 'required|string|max:255',
            'username'   => 'nullable|string|max:255',
            'password'   => 'nullable|string|max:255',
            'remark'     => 'nullable|string',
        ]);
    }
}
