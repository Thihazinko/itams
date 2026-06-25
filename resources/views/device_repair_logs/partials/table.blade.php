@php
    $canEdit = auth()->user()->canEdit('devices');
    $statusTone = ['In Progress' => 'warning', 'Completed' => 'success'];
@endphp

<form id="drlBulkForm" method="POST" action="{{ route('device-repair-logs.bulk-destroy') }}">
    @csrf
    @method('DELETE')

    @if($canEdit)
    <div id="drlBulkToolbar" class="d-none align-items-center gap-2 mb-2">
        <span class="text-muted small"><strong id="drlBulkCount">0</strong> selected</span>
        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete selected</button>
        <button type="button" id="drlBulkClear" class="btn btn-sm btn-outline-secondary">Clear</button>
    </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        @if($canEdit)<th style="width: 38px;"><input type="checkbox" id="drlSelectAll" class="form-check-input"></th>@endif
                        <th style="width: 48px;">No</th>
                        <th style="width: 110px;">Date</th>
                        <th>Item Name</th>
                        <th style="min-width: 220px;">Repair Process</th>
                        <th style="width: 120px;">Status</th>
                        <th>Remark</th>
                        @if($canEdit)<th style="width: 96px;" class="text-end">Actions</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            @if($canEdit)
                            <td><input type="checkbox" name="ids[]" value="{{ $log->id }}" class="form-check-input drl-row-check"></td>
                            @endif
                            <td class="text-muted">{{ $logs->firstItem() + $loop->index }}</td>
                            <td>{{ optional($log->repair_date)->format('Y-m-d') ?: '—' }}</td>
                            <td><span class="fw-medium">{{ $log->device_label }}</span></td>
                            <td><span class="d-inline-block text-truncate" style="max-width: 320px;" title="{{ $log->repair_process }}">{{ $log->repair_process }}</span></td>
                            <td><span class="badge bg-{{ $statusTone[$log->status] ?? 'secondary' }}-subtle text-{{ $statusTone[$log->status] ?? 'secondary' }}-emphasis">{{ $log->status }}</span></td>
                            <td><span class="d-inline-block text-truncate" style="max-width: 240px;" title="{{ $log->remark }}">{{ $log->remark ?: '—' }}</span></td>
                            @if($canEdit)
                            <td class="text-end">
                                <a href="{{ route('device-repair-logs.edit', $log) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <button type="button" class="btn btn-sm btn-outline-danger drl-delete-single"
                                        data-action="{{ route('device-repair-logs.destroy', $log) }}"
                                        data-label="{{ $log->device_label }} ({{ optional($log->repair_date)->format('Y-m-d') }})"
                                        title="Delete"><i class="bi bi-trash"></i></button>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit ? 8 : 6 }}" class="text-center text-muted py-4">
                                No repair logs found. @if($canEdit)<a href="{{ route('device-repair-logs.create') }}">Add the first one</a>.@endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

@if($logs->hasPages())
    <div class="mt-3">{{ $logs->links() }}</div>
@endif
