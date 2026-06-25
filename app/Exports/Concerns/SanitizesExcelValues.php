<?php

namespace App\Exports\Concerns;

/**
 * Cleans cell values so the Xlsx writer can serialize them.
 *
 * An .xlsx file is XML, so any value containing bytes that are illegal in
 * XML 1.0 (most C0 control characters) or invalid UTF-8 sequences makes
 * PhpSpreadsheet's Xlsx writer fail with a 500 — even though the exact same
 * value exports fine to CSV (which writes raw bytes). This strips those bytes
 * and truncates to Excel's per-cell limit so the export can't crash on a
 * single bad row.
 */
trait SanitizesExcelValues
{
    protected function clean($value)
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        // Re-encode any invalid UTF-8 byte sequences to keep the value well-formed
        // (mbstring is always available with Laravel; iconv is not guaranteed).
        if (! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        // Remove control characters that are not permitted in XML 1.0.
        // Keep tab (0x09), line feed (0x0A) and carriage return (0x0D).
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        // Excel caps a cell at 32,767 characters.
        if (mb_strlen($value) > 32767) {
            $value = mb_substr($value, 0, 32767);
        }

        return $value;
    }
}
