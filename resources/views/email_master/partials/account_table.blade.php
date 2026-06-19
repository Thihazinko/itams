@php
    $isAdmin = auth()->user()->isAdmin();
    $canEdit = auth()->user()->canEdit('email_master');
@endphp

<form id="emBulkForm" action="{{ route('email-accounts.bulk-destroy') }}" method="POST">
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
                        <th>Status</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Address</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Remark</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $i => $account)
                        @php $inactive = $account->status === 'Inactive'; @endphp
                        <tr class="{{ $inactive ? 'em-inactive' : '' }}">
                            @if($canEdit)
                                <td><input type="checkbox" name="ids[]" value="{{ $account->id }}" class="form-check-input em-row-check"></td>
                            @endif
                            <td class="text-muted small">{{ ($accounts->firstItem() ?? 1) + $i }}</td>
                            <td>
                                @php $stone = $inactive ? 'secondary' : 'success'; @endphp
                                <span class="badge bg-{{ $stone }}-subtle text-{{ $stone }}-emphasis">{{ $account->status }}</span>
                            </td>
                            <td class="fw-semibold">{{ $account->name }}</td>
                            <td>{{ $account->department ?: '—' }}</td>
                            <td><span class="text-break">{{ $account->address }}</span></td>
                            <td>{{ $account->username ?: '—' }}</td>
                            <td>
                                @if($account->password)
                                    <span class="em-pw d-inline-flex align-items-center gap-1" data-pw="{{ $account->password }}" data-revealed="0">
                                        <code class="em-pw-dots small">••••••••</code>
                                        <button type="button" class="btn-icon-soft em-pw-toggle" title="Show / hide"><i class="bi bi-eye"></i></button>
                                        <button type="button" class="btn-icon-soft em-pw-copy" title="Copy"><i class="bi bi-clipboard"></i></button>
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-truncate" style="max-width: 200px;" title="{{ $account->remark }}">{{ $account->remark ?: '—' }}</td>
                            <td class="text-end text-nowrap pe-3">
                                @if($canEdit)
                                    <a href="{{ route('email-accounts.edit', $account) }}" class="btn-icon-soft" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn-icon-soft text-danger em-delete-single"
                                            title="Delete" aria-label="Delete"
                                            data-action="{{ route('email-accounts.destroy', $account) }}"
                                            data-label="{{ $account->name }}"
                                            data-detail="{{ $account->address }}"><i class="bi bi-trash"></i></button>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit ? 10 : 9 }}" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                    <div class="fw-semibold">No {{ $accountType }} accounts found</div>
                                    <div class="small">
                                        @if($search !== '')
                                            Try clearing the search or <a href="{{ route('email-master.index', ['tab' => $tab]) }}">view all</a>.
                                        @elseif($canEdit)
                                            <a href="{{ route('email-accounts.create', ['type' => $accountType]) }}">Add the first {{ $accountType }} account</a> to get started.
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

<div class="mt-3">{{ $accounts->links() }}</div>
