<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskItem extends Model
{
    protected $fillable = ['task_category_id', 'name', 'sort_order'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'task_category_id');
    }

    public function manHours(): HasMany
    {
        return $this->hasMany(TaskManHour::class);
    }
}
