@php
    $canEdit = auth()->user()->canEdit('email_master');
@endphp

<form id="emBulkForm" action="{{ route('email-aliases.bulk-destroy') }}" method="POST">
    @csrf @method('DELETE')

    @if($canEdit)
    <div id="emBulkToolbar" class="card mb-2 d-none">
        <div class="card-body py-2 d-flex justify-content-between align-items-center">
            <span class="small">
                <i class="bi bi-check2-square text-primary"></i>
                <strong id="emBulkCount">0</strong> selected
            </span>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="emBulkClear">Clear</button>
                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete selected</button>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @if($canEdit)
                            <th style="width: 38px;"><input type="checkbox" id="emSelectAll" class="form-check-input" title="Select all on page"></th>
                        @endif
                        <th style="width: 56px;">No</th>
                        <th>Main Email</th>
                        <th>Mailing Addresses</th>
                        <th>Remark</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($aliases as $i => $alias)
                        <tr>
                            @if($canEdit)
                                <td><input type="checkbox" name="ids[]" value="{{ $alias->id }}" class="form-check-input em-row-check"></td>
                            @endif
                            <td class="text-muted small">{{ ($aliases->firstItem() ?? 1) + $i }}</td>
                            <td class="fw-semibold text-break">{{ $alias->main_email }}</td>
                            <td>
                                @forelse($alias->members as $member)
                                    <span class="badge bg-light text-dark border mb-1">{{ $member->address }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                                @if($alias->members->isNotEmpty())
                                    <span class="text-muted small d-block">{{ $alias->members->count() }} member{{ $alias->members->count() === 1 ? '' : 's' }}</span>
                                @endif
                            </td>
                            <td class="text-truncate" style="max-width: 200px;" title="{{ $alias->remark }}">{{ $alias->remark ?: '—' }}</td>
                            <td class="text-end text-nowrap pe-3">
                                @if($canEdit)
                                    <a href="{{ route('email-aliases.edit', $alias) }}" class="btn-icon-soft" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn-icon-soft text-danger em-delete-single"
                                            title="Delete" aria-label="Delete"
                                            data-action="{{ route('email-aliases.destroy', $alias) }}"
                                            data-label="{{ $alias->main_email }}"
                                            data-detail="{{ $alias->members->count() }} member(s)"><i class="bi bi-trash"></i></button>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit ? 6 : 5 }}" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                    <div class="fw-semibold">No aliases found</div>
                                    <div class="small">
                                        @if($search !== '')
                                            Try clearing the search or <a href="{{ route('email-master.index', ['tab' => 'alias']) }}">view all</a>.
                                        @elseif($canEdit)
                                            <a href="{{ route('email-aliases.create') }}">Add the first alias</a> to get started.
                                        @else
                                            No records have been added yet.
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

<div class="mt-3">{{ $aliases->links() }}</div>
