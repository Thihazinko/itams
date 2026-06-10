@extends('layouts.app')
@section('title', $asset->computer_id)
@section('content')
@php
    $statusTone = match($asset->status) {
        'Active'          => 'success',
        'Free'            => 'info',
        'Damage'          => 'danger',
        'Retirement'      => 'secondary',
        'Low Performance' => 'warning',
        default           => 'secondary',
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $asset->computer_id }}</h1>
        <div class="page-subtitle">
            <span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}-emphasis me-1">{{ $asset->status }}</span>
            {{ $asset->hostname }}
            @if($asset->employee_name) &middot; assigned to {{ $asset->employee_name }} @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('pc-assets.edit', $asset) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <a href="{{ route('pc-assets.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-info-circle text-primary"></i>
                <strong>Assignment &amp; Location</strong>
            </div>
            <div class="card-body">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-4 text-muted">Employee</dt>
                    <dd class="col-sm-8">{{ $asset->employee_name ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Department</dt>
                    <dd class="col-sm-8">{{ $asset->department ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Location</dt>
                    <dd class="col-sm-8">
                        @if($asset->location === 'WFH')
                            <i class="bi bi-house-door text-muted"></i> Work From Home
                        @else
                            <i class="bi bi-building text-muted"></i> Office
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-cpu text-primary"></i>
                <strong>Hardware</strong>
            </div>
            <div class="card-body">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-4 text-muted">Brand / Model</dt>
                    <dd class="col-sm-8">{{ trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')) ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Serial Number</dt>
                    <dd class="col-sm-8">{{ $asset->serial_number ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Operating System</dt>
                    <dd class="col-sm-8">{{ $asset->operating_system ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">License Key</dt>
                    <dd class="col-sm-8">{{ $asset->license_key ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Expire Date</dt>
                    <dd class="col-sm-8">
                        @if($asset->expire_permanent)
                            <span class="badge bg-success-subtle text-success-emphasis">Permanent</span>
                        @else
                            {{ $asset->expire_date?->format('Y-m-d') ?? '—' }}
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">CPU</dt>
                    <dd class="col-sm-8">{{ $asset->cpu ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">RAM / SSD / HDD</dt>
                    <dd class="col-sm-8">{{ $asset->ram ?: '—' }} &middot; {{ $asset->ssd ?: '—' }} &middot; {{ $asset->hdd ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Display</dt>
                    <dd class="col-sm-8">{{ $asset->display ?: '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-calendar-check text-primary"></i>
                <strong>Lifecycle</strong>
            </div>
            <div class="card-body">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-4 text-muted">Purchased Date</dt>
                    <dd class="col-sm-8">{{ $asset->purchased_date?->format('Y-m-d') ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Warranty Period</dt>
                    <dd class="col-sm-8">{{ $asset->warranty_period ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Warranty Status</dt>
                    <dd class="col-sm-8">
                        @php
                            $warrantyTone = match($asset->warranty_status) {
                                'In Warranty'   => 'success',
                                'Expiring Soon' => 'warning',
                                'Expired'       => 'danger',
                                default         => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $warrantyTone }}-subtle text-{{ $warrantyTone }}-emphasis">{{ $asset->warranty_status }}</span>
                        @if($asset->warranty_end_date)
                            <span class="text-muted small ms-1">until {{ $asset->warranty_end_date->format('Y-m-d') }}</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Remarks</dt>
                    <dd class="col-sm-8" style="white-space: pre-line;">{{ $asset->remarks ?: '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-box-seam text-primary"></i>
                <strong>Software List</strong>
                <span class="text-muted small ms-2">Installed software on this PC.</span>
            </div>
            <div class="card-body">
                @if($asset->software->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Software</th>
                                    <th>Version</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($asset->software as $software)
                                    <tr>
                                        <td class="fw-semibold">{{ $software->name }}</td>
                                        <td>{{ $software->version ?: '—' }}</td>
                                        <td>{{ $software->notes ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted small mb-0">No software listed yet. Use Edit to add software.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-2">
                <span class="d-flex align-items-center gap-2"><i class="bi bi-shield-lock text-primary"></i> <strong class="small">Credentials</strong></span>
                <button type="button" class="btn btn-sm btn-icon-soft" data-credential-toggle title="Show / hide passwords">
                    <i class="bi bi-eye-slash" data-credential-icon></i>
                </button>
            </div>
            <div class="card-body p-2 small">
                <div class="mb-2">
                    <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: .68rem; letter-spacing: .05em;">Username</small>
                    @if($asset->username)
                        <div class="d-flex align-items-center gap-2">
                            <code class="flex-grow-1 text-truncate" data-copy-target>{{ $asset->username }}</code>
                            <button type="button" class="btn btn-sm btn-icon-soft" data-copy="{{ $asset->username }}" title="Copy"><i class="bi bi-clipboard"></i></button>
                        </div>
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </div>
                <div class="mb-2">
                    <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: .68rem; letter-spacing: .05em;">Password</small>
                    @if($asset->password)
                        <div class="d-flex align-items-center gap-2">
                            <code class="flex-grow-1 text-truncate" data-credential data-value="{{ $asset->password }}">••••••••</code>
                            <button type="button" class="btn btn-sm btn-icon-soft" data-copy="{{ $asset->password }}" title="Copy"><i class="bi bi-clipboard"></i></button>
                        </div>
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </div>
                <div>
                    <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: .68rem; letter-spacing: .05em;">Admin Password</small>
                    @if($asset->admin_password)
                        <div class="d-flex align-items-center gap-2">
                            <code class="flex-grow-1 text-truncate" data-credential data-value="{{ $asset->admin_password }}">••••••••</code>
                            <button type="button" class="btn btn-sm btn-icon-soft" data-copy="{{ $asset->admin_password }}" title="Copy"><i class="bi bi-clipboard"></i></button>
                        </div>
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body small">
                <div class="text-muted text-uppercase fw-semibold mb-2" style="font-size: .68rem; letter-spacing: .05em;">Audit</div>
                <div class="d-flex justify-content-between"><span class="text-muted">Modified by</span><span class="fw-semibold">{{ $asset->modified_by ?: '—' }}</span></div>
                <div class="d-flex justify-content-between mt-1"><span class="text-muted">Last update</span><span>{{ $asset->updated_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
                <div class="d-flex justify-content-between mt-1"><span class="text-muted">Created</span><span>{{ $asset->created_at?->format('Y-m-d') ?? '—' }}</span></div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-people text-primary"></i>
                <strong>Employee History</strong>
                <span class="text-muted small ms-2">Who has used this PC over time.</span>
            </div>
            <div class="card-body">
                @forelse($asset->assignments as $assignment)
                    <div class="d-flex gap-3 py-2 @if(!$loop->last) border-bottom border-light-subtle @endif">
                        <div class="text-center" style="min-width: 36px;">
                            @if($assignment->released_at)
                                <i class="bi bi-person text-muted fs-5"></i>
                            @else
                                <i class="bi bi-person-fill-check text-success fs-5"></i>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                                <span class="fw-semibold">{{ $assignment->employee_name ?: '—' }}</span>
                                @if($assignment->released_at)
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">Past</span>
                                @else
                                    <span class="badge bg-success-subtle text-success-emphasis">Current</span>
                                @endif
                            </div>
                            <div class="text-muted small">
                                @if($assignment->department){{ $assignment->department }} &middot; @endif
                                {{ $assignment->assigned_at?->format('Y-m-d') ?? '—' }}
                                &rarr;
                                {{ $assignment->released_at?->format('Y-m-d') ?? 'present' }}
                            </div>
                            @if($assignment->recorded_by)
                                <div class="text-muted" style="font-size: .72rem;">recorded by {{ $assignment->recorded_by }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No assignment history yet. It will start tracking when this PC is assigned to an employee.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const toggleBtn = document.querySelector('[data-credential-toggle]');
        const toggleIcon = document.querySelector('[data-credential-icon]');
        const credentialEls = document.querySelectorAll('[data-credential]');
        let revealed = false;

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                revealed = !revealed;
                credentialEls.forEach(el => {
                    el.textContent = revealed ? el.dataset.value : '••••••••';
                });
                if (toggleIcon) {
                    toggleIcon.className = revealed ? 'bi bi-eye' : 'bi bi-eye-slash';
                }
            });
        }

        // navigator.clipboard only exists in a secure context (HTTPS/localhost).
        // Over plain HTTP in production it's undefined, so fall back to execCommand.
        async function copyText(text) {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return;
            }

            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.top = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                if (!document.execCommand('copy')) {
                    throw new Error('execCommand returned false');
                }
            } finally {
                document.body.removeChild(textarea);
            }
        }

        document.querySelectorAll('[data-copy]').forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    await copyText(btn.dataset.copy);
                    const icon = btn.querySelector('i');
                    const original = icon.className;
                    icon.className = 'bi bi-check2 text-success';
                    setTimeout(() => { icon.className = original; }, 1200);
                } catch (e) {
                    alert('Copy failed. Select the text manually.');
                }
            });
        });
    })();
</script>
@endpush
