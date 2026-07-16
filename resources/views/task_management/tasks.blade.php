@extends('layouts.app')

@section('title', 'Task Management — Tasks')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Manage Tasks</h1>
        <div class="page-subtitle">Categories and their tasks — the rows shown on the monthly man-hour sheet.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('task-management.index') }}" class="quick-action"><i class="bi bi-table"></i> Monthly Sheet</a>
    </div>
</div>

@if($canEdit)
    {{-- Add a new category --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('task-categories.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-sm">
                    <label class="form-label small text-muted mb-1">New category name</label>
                    <input type="text" name="name" class="form-control" required maxlength="255" placeholder="e.g. Monitoring">
                </div>
                <div class="col-sm-auto">
                    <label class="form-label small text-muted mb-1">Plan man-hours</label>
                    <div class="input-group" style="width: 160px;">
                        <input type="number" name="plan_hours" class="form-control" min="0" max="999999" step="0.5" value="0">
                        <span class="input-group-text">h</span>
                    </div>
                </div>
                <div class="col-sm-auto">
                    <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Category</button>
                </div>
            </form>
        </div>
    </div>
@endif

@forelse($categories as $category)
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2 flex-wrap">
            @php
                $planHours = rtrim(rtrim(number_format((float) $category->plan_hours, 2, '.', ''), '0'), '.');
                $planHours = $planHours === '' ? '0' : $planHours;
            @endphp
            @if($canEdit)
                <form method="POST" action="{{ route('task-categories.update', $category) }}" class="d-flex align-items-center gap-2 flex-grow-1 flex-wrap">
                    @csrf @method('PUT')
                    <input type="text" name="name" value="{{ $category->name }}" class="form-control form-control-sm fw-semibold" style="max-width: 320px;" required maxlength="255">
                    <div class="input-group input-group-sm" style="width: 150px;">
                        <span class="input-group-text" title="Planned man-hours">Plan</span>
                        <input type="number" name="plan_hours" value="{{ $planHours }}" class="form-control" min="0" max="999999" step="0.5" title="Planned man-hours">
                        <span class="input-group-text">h</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" title="Save category"><i class="bi bi-check-lg"></i></button>
                </form>
                <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $category->items->count() }} task{{ $category->items->count() === 1 ? '' : 's' }}</span>
                <form method="POST" action="{{ route('task-categories.destroy', $category) }}"
                      onsubmit="return confirm('Delete category “{{ $category->name }}” and all its tasks (plus their man-hours)?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="Delete category"><i class="bi bi-trash"></i></button>
                </form>
            @else
                <span class="fw-semibold">{{ $category->name }}</span>
                <span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-clock-history me-1"></i>Plan {{ $planHours }}h</span>
                <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $category->items->count() }}</span>
            @endif
        </div>
        <ul class="list-group list-group-flush">
            @forelse($category->items as $task)
                <li class="list-group-item d-flex align-items-center gap-2">
                    @if($canEdit)
                        <form method="POST" action="{{ route('task-items.update', $task) }}" class="d-flex align-items-center gap-2 flex-grow-1">
                            @csrf @method('PUT')
                            <input type="text" name="name" value="{{ $task->name }}" class="form-control form-control-sm" required maxlength="255">
                            <button class="btn btn-sm btn-outline-secondary" title="Rename task"><i class="bi bi-check-lg"></i></button>
                        </form>
                        <form method="POST" action="{{ route('task-items.destroy', $task) }}"
                              onsubmit="return confirm('Delete task “{{ $task->name }}” and its man-hours?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Delete task"><i class="bi bi-trash"></i></button>
                        </form>
                    @else
                        <span>{{ $task->name }}</span>
                    @endif
                </li>
            @empty
                <li class="list-group-item text-muted small">No tasks in this category yet.</li>
            @endforelse

            @if($canEdit)
                <li class="list-group-item bg-body-tertiary">
                    <form method="POST" action="{{ route('task-items.store') }}" class="d-flex align-items-center gap-2">
                        @csrf
                        <input type="hidden" name="task_category_id" value="{{ $category->id }}">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Add a task to {{ $category->name }}…" required maxlength="255">
                        <button class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> Add Task</button>
                    </form>
                </li>
            @endif
        </ul>
    </div>
@empty
    <div class="alert alert-info">No categories yet. @if($canEdit)Add one above.@endif</div>
@endforelse
@endsection
