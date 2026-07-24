<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseContractAttachment extends Model
{
    protected $fillable = [
        'license_contract_id', 'file_path', 'original_name', 'label',
        'mime_type', 'size', 'uploaded_by',
    ];

    public function licenseContract(): BelongsTo
    {
        return $this->belongsTo(LicenseContract::class);
    }

    /**
     * A display name for the file: the user's label if set, otherwise the
     * original upload filename, otherwise the stored basename.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->label
            ?: ($this->original_name ?: basename($this->file_path));
    }

    /**
     * Human-readable file size (e.g. "1.4 MB"). Null when size is unknown.
     */
    public function getHumanSizeAttribute(): ?string
    {
        if (! $this->size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = (float) $this->size;
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return ($i === 0 ? (int) $bytes : number_format($bytes, 1)) . ' ' . $units[$i];
    }

    /**
     * A Bootstrap Icons class chosen from the file extension.
     */
    public function getIconAttribute(): string
    {
        $ext = strtolower(pathinfo($this->original_name ?: $this->file_path, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf'                        => 'bi-file-earmark-pdf',
            'jpg', 'jpeg', 'png', 'webp', 'gif' => 'bi-file-earmark-image',
            'doc', 'docx'                => 'bi-file-earmark-word',
            'xls', 'xlsx', 'csv'         => 'bi-file-earmark-spreadsheet',
            default                      => 'bi-file-earmark',
        };
    }
}
