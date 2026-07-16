<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDailyEntry extends Model
{
    /**
     * The fixed working-hour slots from the "Daily Task" sheet — an 8-hour day
     * with a 12:00–13:00 lunch break. Each slot represents one man-hour.
     */
    public const SLOTS = [
        1 => '08:00 to 09:00',
        2 => '09:00 to 10:00',
        3 => '10:00 to 11:00',
        4 => '11:00 to 12:00',
        5 => '13:00 to 14:00',
        6 => '14:00 to 15:00',
        7 => '15:00 to 16:00',
        8 => '16:00 to 17:00',
    ];

    /** Default start/end time for each slot (editable per entry). */
    public const SLOT_TIMES = [
        1 => ['08:00', '09:00'],
        2 => ['09:00', '10:00'],
        3 => ['10:00', '11:00'],
        4 => ['11:00', '12:00'],
        5 => ['13:00', '14:00'],
        6 => ['14:00', '15:00'],
        7 => ['15:00', '16:00'],
        8 => ['16:00', '17:00'],
    ];

    public const WORK_TYPES  = ['Regular', 'Temporary'];
    public const STUDY_TYPES = ['Work', 'Study'];

    protected $fillable = [
        'user_id', 'work_date', 'slot', 'start_time', 'end_time',
        'task_category_id', 'task_item_id',
        'project_name', 'expense_name', 'work_type', 'study_type', 'task_detail',
        'created_by', 'modified_by',
    ];

    protected $casts = [
        'work_date' => 'date',
        'slot'      => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'task_category_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskItem::class, 'task_item_id');
    }

    /** Label for this entry's time slot, e.g. "09:00 to 10:00". */
    public function slotLabel(): string
    {
        return self::SLOTS[$this->slot] ?? (string) $this->slot;
    }

    /** Man-hours for this entry, from its start/end span (defaults to 1h). */
    public function hours(): float
    {
        return self::hoursBetween($this->start_time, $this->end_time);
    }

    /**
     * Duration in hours between two "HH:MM(:SS)" times. Falls back to 1.0 when a
     * time is missing or the span isn't positive, keeping the original
     * "one filled slot = one hour" behaviour for un-edited rows.
     */
    public static function hoursBetween($start, $end): float
    {
        $s = self::toMinutes($start);
        $e = self::toMinutes($end);
        if ($s === null || $e === null) {
            return 1.0;
        }

        $diff = ($e - $s) / 60;

        return $diff > 0 ? round($diff, 2) : 1.0;
    }

    /** Parse "HH:MM" or "HH:MM:SS" into minutes past midnight, or null. */
    private static function toMinutes($time): ?int
    {
        if (! $time || ! preg_match('/^(\d{1,2}):(\d{2})/', (string) $time, $m)) {
            return null;
        }

        return ((int) $m[1]) * 60 + (int) $m[2];
    }
}
