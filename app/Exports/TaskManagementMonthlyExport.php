<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * A month's Daily Task workbook: one worksheet per member. Exporting a single
 * member yields a one-sheet workbook; exporting all members yields one sheet
 * each, so the same export serves both cases.
 */
class TaskManagementMonthlyExport implements WithMultipleSheets
{
    /** @param Collection<int, \App\Models\User> $members */
    public function __construct(
        protected Collection $members,
        protected int $year,
        protected int $month,
        protected ?Carbon $from = null,
        protected ?Carbon $to = null,
    ) {
    }

    public function sheets(): array
    {
        $sheets = [];
        $seen   = [];

        foreach ($this->members as $member) {
            $title = $this->uniqueTitle($member->name, $seen);
            $seen[mb_strtolower($title)] = true;

            $sheets[] = new TaskMonthlyMemberSheet($member, $this->year, $this->month, $title, $this->from, $this->to);
        }

        return $sheets;
    }

    /**
     * A worksheet title Excel accepts: no \ / ? * [ ] : characters, at most 31
     * chars, and unique within the workbook (duplicates get a " (n)" suffix).
     */
    private function uniqueTitle(string $name, array $seen): string
    {
        $base = trim(preg_replace('/[\\\\\/\?\*\[\]:]+/', ' ', $name));
        $base = $base === '' ? 'Member' : mb_substr($base, 0, 31);

        $title = $base;
        $n     = 2;
        while (isset($seen[mb_strtolower($title)])) {
            $suffix = ' (' . $n . ')';
            $title  = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
            $n++;
        }

        return $title;
    }
}
