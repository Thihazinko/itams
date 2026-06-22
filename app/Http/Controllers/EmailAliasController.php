<?php

namespace App\Http\Controllers;

use App\Exports\EmailAliasesExport;
use App\Exports\EmailAliasesTemplate;
use App\Imports\EmailAliasesImport;
use App\Models\EmailAlias;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class EmailAliasController extends Controller
{
    public function create()
    {
        return view('email_master.aliases.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $alias = EmailAlias::create([
            'main_email'  => $data['main_email'],
            'remark'      => $data['remark'] ?? null,
            'modified_by' => $request->user()->name,
        ]);

        $this->syncMembers($alias, $data['members'] ?? []);

        ActivityLogger::log(
            action: 'created',
            description: "Created alias {$alias->main_email}",
            subject: $alias,
        );

        return redirect()
            ->route('email-master.index', ['tab' => 'alias'])
            ->with('success', 'Alias created.');
    }

    public function edit(EmailAlias $emailAlias)
    {
        $emailAlias->load('members');

        return view('email_master.aliases.edit', ['alias' => $emailAlias]);
    }

    public function update(Request $request, EmailAlias $emailAlias)
    {
        $data = $this->validateData($request, $emailAlias);

        $emailAlias->update([
            'main_email'  => $data['main_email'],
            'remark'      => $data['remark'] ?? null,
            'modified_by' => $request->user()->name,
        ]);

        // Replace the member list wholesale — simplest correct sync for a small set.
        $emailAlias->members()->delete();
        $this->syncMembers($emailAlias, $data['members'] ?? []);

        ActivityLogger::log(
            action: 'updated',
            description: "Updated alias {$emailAlias->main_email}",
            subject: $emailAlias,
        );

        return redirect()
            ->route('email-master.index', ['tab' => 'alias'])
            ->with('success', 'Alias updated.');
    }

    public function destroy(EmailAlias $emailAlias)
    {
        $label = $emailAlias->main_email;

        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted alias {$label}",
            subject: $emailAlias,
        );

        $emailAlias->delete();

        return redirect()
            ->route('email-master.index', ['tab' => 'alias'])
            ->with('success', 'Alias deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:email_aliases,id',
        ]);

        $aliases = EmailAlias::whereIn('id', $data['ids'])->get();

        foreach ($aliases as $alias) {
            ActivityLogger::log(
                action: 'deleted',
                description: "Deleted alias {$alias->main_email} [bulk]",
                subject: $alias,
            );
            $alias->delete();
        }

        $count = $aliases->count();

        return redirect()
            ->route('email-master.index', ['tab' => 'alias'])
            ->with('success', "Deleted {$count} alias(es).");
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new EmailAliasesExport(), 'email-aliases-' . now()->format('Ymd-His') . '.' . $ext, $format);
    }

    public function template(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new EmailAliasesTemplate(), 'email-aliases-template.' . $ext, $format);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $import = new EmailAliasesImport();
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        $imported = $import->imported;
        $skipped = $import->skipped;
        $failed = count($import->failures);

        ActivityLogger::log(
            action: 'imported',
            description: "Imported {$imported} alias(es)"
                . ($skipped > 0 ? " ({$skipped} duplicate(s) skipped)" : '')
                . ($failed > 0 ? " ({$failed} failed)" : ''),
            properties: ['imported' => $imported, 'skipped' => $skipped, 'failed' => $failed],
        );

        $skippedNote = $skipped > 0 ? " {$skipped} duplicate(s) skipped." : '';

        if ($failed > 0) {
            $msg = "Imported {$imported} new row(s); {$failed} row(s) failed validation.{$skippedNote}";
            $details = collect($import->failures)
                ->take(10)
                ->map(fn ($f) => 'Row ' . ($f['row'] ?? '?') . ' (' . $f['attribute'] . '): ' . implode(', ', $f['errors']))
                ->implode(' | ');
            return back()->with('error', $msg . ' ' . $details);
        }

        return back()->with('success', "Imported {$imported} alias(es) successfully.{$skippedNote}");
    }

    /**
     * Create member rows from a list of addresses, ignoring blanks and dupes.
     */
    private function syncMembers(EmailAlias $alias, array $members): void
    {
        $seen = [];
        foreach ($members as $address) {
            $address = trim((string) $address);
            $key = strtolower($address);
            if ($address === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $alias->members()->create(['address' => $address]);
        }
    }

    private function validateData(Request $request, ?EmailAlias $ignore = null): array
    {
        // main_email is the unique identifier for an alias, matching the import's dedup.
        $mainUnique = Rule::unique('email_aliases', 'main_email');
        if ($ignore !== null) {
            $mainUnique->ignore($ignore->id);
        }

        return $request->validate([
            'main_email' => ['required', 'string', 'max:255', $mainUnique],
            'remark'     => 'nullable|string',
            'members'    => 'nullable|array',
            'members.*'  => 'nullable|string|max:255',
        ], [
            'main_email.unique' => 'This main email already exists.',
        ]);
    }
}
