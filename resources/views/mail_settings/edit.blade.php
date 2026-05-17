@extends('layouts.app')

@section('title', 'Mail Settings')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Mail Settings</h1>
        <div class="page-subtitle">SMTP delivery and renewal-reminder email configuration.</div>
    </div>
    <div class="d-flex gap-2">
        @if($settings->enabled)
            <span class="badge bg-success-subtle text-success-emphasis align-self-center"><i class="bi bi-check2-circle"></i> DB SMTP enabled</span>
        @else
            <span class="badge bg-secondary-subtle text-secondary-emphasis align-self-center"><i class="bi bi-slash-circle"></i> Using .env</span>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('mail-settings.update') }}">
            @csrf
            @method('PUT')

            {{-- Enable / source toggle --}}
            <div class="card mb-3 mail-enable-card {{ $settings->enabled ? 'is-on' : '' }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="mail-enable-icon">
                        <i class="bi bi-database-fill-gear"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">Database SMTP settings</div>
                        <div class="text-muted small">When off, mail uses the <code>.env</code> configuration instead.</div>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" id="enabled" class="form-check-input" role="switch" @checked($settings->enabled) style="width: 3rem; height: 1.6rem;">
                    </div>
                </div>
            </div>

            {{-- Connection --}}
            <div class="card mb-3">
                <div class="card-header bg-transparent d-flex align-items-center gap-2">
                    <i class="bi bi-hdd-network text-primary"></i><strong>SMTP Connection</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Mailer</label>
                            <select name="mailer" class="form-select">
                                <option value="smtp"     @selected($settings->mailer === 'smtp')>SMTP</option>
                                <option value="log"      @selected($settings->mailer === 'log')>Log (debug)</option>
                                <option value="sendmail" @selected($settings->mailer === 'sendmail')>Sendmail</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" name="host" value="{{ old('host', $settings->host) }}" class="form-control" placeholder="smtp.gmail.com">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Port</label>
                            <input type="number" name="port" value="{{ old('port', $settings->port) }}" class="form-control" placeholder="587">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Connection security</label>
                            <select name="encryption" class="form-select">
                                <option value=""    @selected(! $settings->encryption)>None</option>
                                <option value="tls" @selected($settings->encryption === 'tls')>STARTTLS</option>
                                <option value="ssl" @selected($settings->encryption === 'ssl')>SSL/TLS</option>
                            </select>
                            <small class="text-muted">Typical: STARTTLS on port 587, SSL/TLS on port 465.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Authentication method</label>
                            <select name="auth_mode" class="form-select">
                                <option value=""         @selected(! $settings->auth_mode)>Auto (let server negotiate)</option>
                                <option value="plain"    @selected($settings->auth_mode === 'plain')>Plain</option>
                                <option value="login"    @selected($settings->auth_mode === 'login')>Login</option>
                                <option value="cram-md5" @selected($settings->auth_mode === 'cram-md5')>CRAM-MD5</option>
                            </select>
                            <small class="text-muted">Leave on Auto unless your provider requires a specific mechanism.</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Authentication --}}
            <div class="card mb-3">
                <div class="card-header bg-transparent d-flex align-items-center gap-2">
                    <i class="bi bi-key text-primary"></i><strong>Authentication</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" name="username" value="{{ old('username', $settings->username) }}" class="form-control border-start-0 ps-0" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" name="password" id="smtpPassword" class="form-control border-start-0 ps-0" placeholder="{{ $settings->password ? '••••••••  (leave blank to keep)' : '' }}" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" id="togglePw" tabindex="-1" title="Show / hide password"><i class="bi bi-eye" id="togglePwIcon"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- From identity --}}
            <div class="card mb-3">
                <div class="card-header bg-transparent d-flex align-items-center gap-2">
                    <i class="bi bi-send text-primary"></i><strong>From Identity</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">From Address</label>
                            <input type="email" name="from_address" value="{{ old('from_address', $settings->from_address) }}" class="form-control" placeholder="noreply@yourdomain.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">From Name</label>
                            <input type="text" name="from_name" value="{{ old('from_name', $settings->from_name) }}" class="form-control" placeholder="ITAMS Notifications">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reminders --}}
            <div class="card mb-3">
                <div class="card-header bg-transparent d-flex align-items-center gap-2">
                    <i class="bi bi-bell text-primary"></i><strong>Renewal Reminders</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Send reminder days before expiry <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="reminder_days_before" value="{{ old('reminder_days_before', $settings->reminder_days_before ?? 30) }}" class="form-control @error('reminder_days_before') is-invalid @enderror" min="1" max="365" required>
                                <span class="input-group-text">days</span>
                            </div>
                            <small class="text-muted">Subscriptions within this window trigger reminders.</small>
                            @error('reminder_days_before')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Reminder Recipients</label>
                            <textarea name="reminder_recipients" rows="3" class="form-control @error('reminder_recipients') is-invalid @enderror" placeholder="One or more emails — separate with comma, semicolon, or newline.&#10;e.g. ops@company.com, admin@company.com">{{ old('reminder_recipients', $settings->reminder_recipients) }}</textarea>
                            <small class="text-muted">Leave empty to fall back to all admin users' emails.</small>
                            @error('reminder_recipients')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-primary"><i class="bi bi-check2"></i> Save Settings</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        {{-- Current Status --}}
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-activity text-primary"></i><strong>Current Status</strong>
            </div>
            <div class="card-body small">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">DB SMTP</span>
                    @if($settings->enabled)
                        <span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-check2-circle"></i> Enabled</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-slash-circle"></i> Disabled</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Active mailer</span>
                    <code>{{ config('mail.default') }}</code>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">From address</span>
                    <code class="text-truncate ms-2" style="max-width: 200px;" title="{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</code>
                </div>
                @if($settings->reminder_days_before)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Reminder window</span>
                    <span>{{ $settings->reminder_days_before }} day{{ $settings->reminder_days_before === 1 ? '' : 's' }} before</span>
                </div>
                @endif
                @php
                    $recipientCount = $settings->reminder_recipients
                        ? count(array_filter(preg_split('/[\s,;]+/', $settings->reminder_recipients)))
                        : 0;
                @endphp
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Recipients</span>
                    @if($recipientCount)
                        <span>{{ $recipientCount }} address{{ $recipientCount === 1 ? '' : 'es' }}</span>
                    @else
                        <span class="text-muted">Admin users (fallback)</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Send Test Email --}}
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-send text-primary"></i><strong>Send Test Email</strong>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Save your settings first, then send a test email to verify your SMTP credentials work.</p>
                <form method="POST" action="{{ route('mail-settings.test') }}">
                    @csrf
                    <label class="form-label">Recipient email</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                        <input type="email" name="test_email" value="{{ auth()->user()->email }}" class="form-control border-start-0 ps-0" required>
                    </div>
                    <button class="btn btn-outline-primary w-100"><i class="bi bi-send"></i> Send Test</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .mail-enable-card {
        transition: border-color .2s ease, background .2s ease;
    }
    .mail-enable-card.is-on {
        border-color: rgba(13, 110, 253, 0.3);
        background: rgba(13, 110, 253, 0.025);
    }
    .mail-enable-icon {
        width: 44px;
        height: 44px;
        border-radius: .6rem;
        background: rgba(108, 117, 125, 0.1);
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
        transition: background .2s ease, color .2s ease;
    }
    .mail-enable-card.is-on .mail-enable-icon {
        background: rgba(13, 110, 253, 0.12);
        color: #0d6efd;
    }
    [data-bs-theme="dark"] .mail-enable-card.is-on {
        border-color: rgba(147, 197, 253, 0.3);
        background: rgba(147, 197, 253, 0.04);
    }
    [data-bs-theme="dark"] .mail-enable-icon {
        background: rgba(255, 255, 255, 0.05);
        color: #cfd8dc;
    }
    [data-bs-theme="dark"] .mail-enable-card.is-on .mail-enable-icon {
        background: rgba(147, 197, 253, 0.15);
        color: #93c5fd;
    }
</style>

<script>
    (function () {
        const pw   = document.getElementById('smtpPassword');
        const btn  = document.getElementById('togglePw');
        const icon = document.getElementById('togglePwIcon');
        if (btn && pw && icon) {
            btn.addEventListener('click', () => {
                const isHidden = pw.type === 'password';
                pw.type = isHidden ? 'text' : 'password';
                icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        }

        const toggle = document.getElementById('enabled');
        const card   = toggle?.closest('.mail-enable-card');
        if (toggle && card) {
            toggle.addEventListener('change', () => {
                card.classList.toggle('is-on', toggle.checked);
            });
        }
    })();
</script>
@endsection
