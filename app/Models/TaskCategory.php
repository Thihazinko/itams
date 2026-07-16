<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskCategory extends Model
{
    protected $fillable = ['name', 'plan_hours', 'sort_order'];

    protected $casts = [
        'plan_hours' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(TaskItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
