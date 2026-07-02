<?php

namespace App\Http\Controllers;

use App\Mail\PurchaseOrderRequest;
use App\Mail\RenewalFinalConfirmation;
use App\Models\Subscription;
use App\Models\SubscriptionRenewal;
use App\Models\User;
use App\Support\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SubscriptionRenewalController extends Controller
{
    public function index(Request $request)
    {
        $query = SubscriptionRenewal::query()->with('subscription');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('approver_name', 'like', "%{$search}%")
                  ->orWhere('approver_email', 'like', "%{$search}%")
                  ->orWhere('second_approver_name', 'like', "%{$search}%")
                  ->orWhere('second_approver_email', 'like', "%{$search}%")
                  ->orWhere('vendor_company', 'like', "%{$search}%")
                  ->orWhereHas('subscription', function ($qs) use ($search) {
                      $qs->where('subscription_name', 'like', "%{$search}%")
                         ->orWhere('project_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $renewals = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $statusCounts = SubscriptionRenewal::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $approverChoices = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('purchase_orders.index', compact('renewals', 'statusCounts', 'approverChoices'));
    }

    public function edit(SubscriptionRenewal $renewal)
    {
        if (! $renewal->isEditable()) {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'Only draft or pending P.O.s can be edited.');
        }

        $renewal->load('subscription');
        $approverChoices = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('purchase_orders.edit', compact('renewal', 'approverChoices'));
    }

    public function update(Request $request, SubscriptionRenewal $renewal)
    {
        if (! $renewal->isEditable()) {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'Only draft or pending P.O.s can be edited.');
        }

        $data = $request->validate([
            'subject'             => 'required|string|max:255',
            'reference'           => 'nullable|string|max:255',
            'vendor_company'      => 'nullable|string|max:255',
            'vendor_name'         => 'nullable|string|max:255',
            'vendor_phone_email'  => 'nullable|string|max:255',
            'approver_user_id'    => 'nullable|exists:users,id',
            'approver_name'       => 'required|string|max:255',
            'approver_email'      => 'required|email|max:255',
            'quantity'            => 'required|integer|min:1',
            'unit_price'          => 'required|numeric|min:0',
            'currency'            => 'required|in:MMK,JPY,USD',
            'notes'               => 'nullable|string|max:2000',
        ]);

        $data['total_amount'] = round((float) $data['quantity'] * (float) $data['unit_price'], 2);

        DB::transaction(function () use ($renewal, $data) {
            $renewal->update($data);
            $renewal->load('subscription');

            if ($renewal->pdf_path && Storage::disk('local')->exists($renewal->pdf_path)) {
                Storage::disk('local')->delete($renewal->pdf_path);
            }
            $renewal->update(['pdf_path' => $this->generatePdf($renewal)]);
        });

        ActivityLogger::log(
            action: 'renewal_updated',
            description: "Updated PO {$renewal->po_number} for {$renewal->subscription->subscription_name}",
            subject: $renewal,
        );

        return redirect()->route('purchase-orders.index')
            ->with('success', "P.O. {$renewal->po_number} updated.");
    }

    public function store(Request $request, Subscription $subscription)
    {
        if ($subscription->activeRenewal()) {
            return back()->with('error', 'A renewal is already in progress for this subscription.');
        }

        $data = $request->validate([
            'subject'             => 'required|string|max:255',
            'reference'           => 'nullable|string|max:255',
            'vendor_company'      => 'nullable|string|max:255',
            'vendor_name'         => 'nullable|string|max:255',
            'vendor_phone_email'  => 'nullable|string|max:255',
            'approver_user_id'    => 'nullable|exists:users,id',
            'approver_name'       => 'required|string|max:255',
            'approver_email'      => 'required|email|max:255',
            'quantity'            => 'required|integer|min:1',
            'unit_price'          => 'required|numeric|min:0',
            'currency'            => 'required|in:MMK,JPY,USD',
            'notes'               => 'nullable|string|max:2000',
        ]);

        $total = round((float) $data['quantity'] * (float) $data['unit_price'], 2);

        $renewal = DB::transaction(function () use ($subscription, $data, $total, $request) {
            $r = SubscriptionRenewal::create([
                'subscription_id'    => $subscription->id,
                'po_number'          => SubscriptionRenewal::generatePoNumber(),
                'po_date'            => Carbon::today(),
                'subject'            => $data['subject'],
                'reference'          => $data['reference'] ?? null,
                'vendor_company'     => $data['vendor_company'] ?? $subscription->vendor_name,
                'vendor_name'        => $data['vendor_name'] ?? null,
                'vendor_phone_email' => $data['vendor_phone_email'] ?? null,
                'approver_user_id'   => $data['approver_user_id'] ?? null,
                'approver_name'      => $data['approver_name'],
                'approver_email'     => $data['approver_email'],
                'quantity'           => $data['quantity'],
                'unit_price'         => $data['unit_price'],
                'total_amount'       => $total,
                'currency'           => $data['currency'],
                'notes'              => $data['notes'] ?? null,
                'signed_token'       => SubscriptionRenewal::generateSignedToken(),
                'status'             => SubscriptionRenewal::STATUS_DRAFT,
                'created_by'         => $request->user()->name,
            ]);

            $r->load('subscription');
            $r->update(['pdf_path' => $this->generatePdf($r)]);

            $subscription->update(['renewal_status' => 'Pending']);

            return $r;
        });

        ActivityLogger::log(
            action: 'renewal_drafted',
            description: "Drafted PO {$renewal->po_number} for {$subscription->subscription_name}",
            subject: $renewal,
            properties: ['total' => $total, 'currency' => $renewal->currency],
        );

        return redirect()->route('purchase-orders.index')
            ->with('success', "Draft P.O. {$renewal->po_number} created. Click the mail icon to send it to the first approver.");
    }

    public function sendFirstMail(Request $request, SubscriptionRenewal $renewal)
    {
        if (! in_array($renewal->status, [SubscriptionRenewal::STATUS_DRAFT, SubscriptionRenewal::STATUS_PENDING], true)) {
            return back()->with('error', 'This P.O. is not in a state where the first approver can be (re)mailed.');
        }

        $renewal->load('subscription');
        $this->ensurePdfExists($renewal);
        $absolutePdf  = Storage::disk('local')->path($renewal->pdf_path);
        $quotationUrl = $this->resolveQuotationUrl($renewal, SubscriptionRenewal::APPROVER_FIRST);

        try {
            Mail::to($renewal->approver_email)->send(
                new PurchaseOrderRequest($renewal, $quotationUrl, $absolutePdf, SubscriptionRenewal::APPROVER_FIRST)
            );
        } catch (\Throwable $e) {
            ActivityLogger::log(
                action: 'mail_failed',
                description: "Failed to mail 1st approver for PO {$renewal->po_number}: " . $e->getMessage(),
                subject: $renewal,
            );
            return back()->with('error', "Mail to first approver failed: {$e->getMessage()}");
        }

        $wasResend = $renewal->isPending();
        $renewal->update([
            'status'           => SubscriptionRenewal::STATUS_PENDING,
            'mailed_first_at'  => now(),
        ]);

        ActivityLogger::log(
            action: $wasResend ? 'renewal_remailed_first' : 'renewal_mailed_first',
            description: ($wasResend ? 'Re-sent' : 'Sent') . " 1st-approval mail for PO {$renewal->po_number} \u{2192} {$renewal->approver_email}",
            subject: $renewal,
        );

        return back()->with('success', "First-approval request sent to {$renewal->approver_email}.");
    }

    public function sendSecondMail(Request $request, SubscriptionRenewal $renewal)
    {
        if (! in_array($renewal->status, [SubscriptionRenewal::STATUS_FIRST_APPROVED, SubscriptionRenewal::STATUS_PENDING_SECOND], true)) {
            return back()->with('error', 'The first approver must approve before the second approver can be mailed.');
        }

        $data = $request->validate([
            'second_approver_user_id' => 'nullable|exists:users,id',
            'second_approver_name'    => 'required|string|max:255',
            'second_approver_email'   => 'required|email|max:255',
        ]);

        $renewal->load('subscription');
        $this->ensurePdfExists($renewal);

        $update = [
            'second_approver_user_id' => $data['second_approver_user_id'] ?? null,
            'second_approver_name'    => $data['second_approver_name'],
            'second_approver_email'   => $data['second_approver_email'],
        ];
        if (! $renewal->second_signed_token) {
            $update['second_signed_token'] = SubscriptionRenewal::generateSignedToken();
        }
        $renewal->update($update);
        $renewal->refresh()->load('subscription');

        $absolutePdf  = Storage::disk('local')->path($renewal->pdf_path);
        $quotationUrl = $this->resolveQuotationUrl($renewal, SubscriptionRenewal::APPROVER_SECOND);

        try {
            Mail::to($renewal->second_approver_email)->send(
                new PurchaseOrderRequest($renewal, $quotationUrl, $absolutePdf, SubscriptionRenewal::APPROVER_SECOND)
            );
        } catch (\Throwable $e) {
            ActivityLogger::log(
                action: 'mail_failed',
                description: "Failed to mail 2nd approver for PO {$renewal->po_number}: " . $e->getMessage(),
                subject: $renewal,
            );
            return back()->with('error', "Mail to second approver failed: {$e->getMessage()}");
        }

        $wasResend = $renewal->isPendingSecond();
        $renewal->update([
            'status'           => SubscriptionRenewal::STATUS_PENDING_SECOND,
            'mailed_second_at' => now(),
        ]);

        ActivityLogger::log(
            action: $wasResend ? 'renewal_remailed_second' : 'renewal_mailed_second',
            description: ($wasResend ? 'Re-sent' : 'Sent') . " 2nd-approval mail for PO {$renewal->po_number} \u{2192} {$renewal->second_approver_email}",
            subject: $renewal,
        );

        return back()->with('success', "Second-approval request sent to {$renewal->second_approver_email}.");
    }

    public function show(Request $request, SubscriptionRenewal $renewal)
    {
        $this->authorizeView($request, $renewal);
        $renewal->load('subscription');

        $token = $request->query('token') ?: $request->route('token');
        $approverStep = $this->approverStepFor($request, $renewal, $token);

        return view('subscriptions.renewals.show', [
            'renewal'      => $renewal,
            'isSigned'     => ! $request->user(),
            'token'        => $token,
            'approverStep' => $approverStep,
        ]);
    }

    public function showSigned(Request $request, SubscriptionRenewal $renewal, string $token)
    {
        $step = $renewal->approverStepForToken($token);
        if (! $step) {
            abort(403, 'Invalid quotation link.');
        }

        $renewal->load('subscription');

        return view('subscriptions.renewals.show', [
            'renewal'      => $renewal,
            'isSigned'     => true,
            'token'        => $token,
            'approverStep' => $step,
        ]);
    }

    public function approve(Request $request, SubscriptionRenewal $renewal)
    {
        $token = $request->input('token');
        $step  = $this->authorizeAction($request, $renewal, $token);

        if ($step === SubscriptionRenewal::APPROVER_FIRST) {
            if (! $renewal->isPending()) {
                return back()->with('error', 'This quotation is no longer pending first approval.');
            }
            $renewal->update([
                'status'      => SubscriptionRenewal::STATUS_FIRST_APPROVED,
                'approved_at' => now(),
            ]);
            ActivityLogger::log(
                action: 'renewal_first_approved',
                description: "First approver signed off on quotation {$renewal->po_number}",
                subject: $renewal,
                overrides: $request->user() ? [] : [
                    'user_name'  => $renewal->approver_name,
                    'user_email' => $renewal->approver_email,
                ],
            );
        } else {
            if (! $renewal->isPendingSecond()) {
                return back()->with('error', 'This quotation is no longer pending second approval.');
            }
            $renewal->update([
                'status'             => SubscriptionRenewal::STATUS_APPROVED,
                'second_approved_at' => now(),
            ]);
            ActivityLogger::log(
                action: 'renewal_second_approved',
                description: "Second approver signed off on quotation {$renewal->po_number}",
                subject: $renewal,
                overrides: $request->user() ? [] : [
                    'user_name'  => $renewal->second_approver_name,
                    'user_email' => $renewal->second_approver_email,
                ],
            );
        }

        return view('subscriptions.renewals.approved', [
            'renewal'      => $renewal->fresh('subscription'),
            'isSigned'     => ! $request->user(),
            'approverStep' => $step,
        ]);
    }

    public function reject(Request $request, SubscriptionRenewal $renewal)
    {
        $token = $request->input('token');
        $step  = $this->authorizeAction($request, $renewal, $token);

        if ($step === SubscriptionRenewal::APPROVER_FIRST) {
            if (! $renewal->isPending()) {
                return back()->with('error', 'This quotation is no longer pending first approval.');
            }
        } else {
            if (! $renewal->isPendingSecond()) {
                return back()->with('error', 'This quotation is no longer pending second approval.');
            }
        }

        $data = $request->validate([
            'rejected_reason' => 'required|string|max:1000',
        ]);

        $rejectorName  = $step === SubscriptionRenewal::APPROVER_SECOND
            ? $renewal->second_approver_name
            : $renewal->approver_name;
        $rejectorEmail = $step === SubscriptionRenewal::APPROVER_SECOND
            ? $renewal->second_approver_email
            : $renewal->approver_email;

        $renewal->update([
            'status'          => SubscriptionRenewal::STATUS_REJECTED,
            'rejected_at'     => now(),
            'rejected_reason' => $data['rejected_reason'],
        ]);

        ActivityLogger::log(
            action: 'renewal_rejected',
            description: "Rejected quotation {$renewal->po_number} ({$step} approver): {$data['rejected_reason']}",
            subject: $renewal,
            overrides: $request->user() ? [] : [
                'user_name'  => $rejectorName,
                'user_email' => $rejectorEmail,
            ],
        );

        return view('subscriptions.renewals.rejected', [
            'renewal'      => $renewal->fresh('subscription'),
            'isSigned'     => ! $request->user(),
            'approverStep' => $step,
        ]);
    }

    public function finalConfirm(Request $request, SubscriptionRenewal $renewal)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Only admins can finalise a renewal.');
        }

        if (! $renewal->isApproved()) {
            return back()->with('error', 'Both approvers must sign off before this renewal can be finalised.');
        }

        $renewal->load('subscription');
        $subscription = $renewal->subscription;

        DB::transaction(function () use ($renewal, $subscription, $user) {
            $newExpiry = $this->extendExpiry(
                $subscription->expire_date,
                $subscription->renewal_type
            );

            $subscription->update([
                'renewal_status'        => 'Renewed',
                'previous_renewal_date' => now()->toDateString(),
                'expire_date'           => $newExpiry,
                'previous_cost'         => $subscription->renewal_cost,
                'renewal_cost'          => $renewal->total_amount,
                'currency'              => $renewal->currency,
                'modified_by'           => $user->name,
            ]);

            $renewal->update([
                'status'              => SubscriptionRenewal::STATUS_FINAL,
                'final_confirmed_at'  => now(),
                'final_confirmed_by'  => $user->name,
            ]);
        });

        $renewal->refresh()->load('subscription');

        $recipients = $this->finalRecipients($renewal);
        $absolutePdf = $renewal->pdf_path ? Storage::disk('local')->path($renewal->pdf_path) : null;

        try {
            Mail::to($recipients)->send(new RenewalFinalConfirmation($renewal, $absolutePdf));
        } catch (\Throwable $e) {
            ActivityLogger::log(
                action: 'mail_failed',
                description: "Final confirmation mail failed for {$renewal->po_number}: " . $e->getMessage(),
                subject: $renewal,
            );
        }

        ActivityLogger::log(
            action: 'renewed',
            description: "Final confirmed renewal {$renewal->po_number} for {$renewal->subscription->subscription_name}; expire_date \u{2192} " . $renewal->subscription->expire_date->format('Y-m-d'),
            subject: $renewal,
            properties: ['recipients' => $recipients],
        );

        return redirect()->route('subscriptions.index')
            ->with('success', "Renewal finalised. Confirmation sent to " . count($recipients) . " recipient(s).");
    }

    public function downloadPdf(Request $request, SubscriptionRenewal $renewal)
    {
        $token = $request->query('token');
        if ($token) {
            if (! $renewal->approverStepForToken($token)) {
                abort(403, 'Invalid quotation link.');
            }
        } else {
            if (! $request->user()) {
                abort(403);
            }
            $this->authorizeView($request, $renewal);
        }

        $renewal->load('subscription');
        $this->ensurePdfExists($renewal);

        return response()->download(
            Storage::disk('local')->path($renewal->pdf_path),
            $renewal->po_number . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function cancel(Request $request, SubscriptionRenewal $renewal)
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        if ($renewal->isComplete()) {
            return back()->with('error', 'This renewal cannot be cancelled.');
        }

        $renewal->update(['status' => SubscriptionRenewal::STATUS_CANCELLED]);

        ActivityLogger::log(
            action: 'renewal_cancelled',
            description: "Cancelled renewal {$renewal->po_number}",
            subject: $renewal,
        );

        return back()->with('success', 'Renewal cancelled.');
    }

    private function ensurePdfExists(SubscriptionRenewal $renewal): void
    {
        if (! $renewal->pdf_path || ! Storage::disk('local')->exists($renewal->pdf_path)) {
            $renewal->update(['pdf_path' => $this->generatePdf($renewal)]);
        }
    }

    private function generatePdf(SubscriptionRenewal $renewal): string
    {
        $pdf = Pdf::loadView('subscriptions.renewals.pdf', [
            'renewal' => $renewal,
            'appName' => config('app.name', 'ITAMS'),
        ])->setPaper('a4');

        $path = 'purchase-orders/' . $renewal->po_number . '.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    private function resolveQuotationUrl(SubscriptionRenewal $renewal, string $step): string
    {
        $userId = $step === SubscriptionRenewal::APPROVER_SECOND
            ? $renewal->second_approver_user_id
            : $renewal->approver_user_id;

        if ($userId) {
            return route('subscriptions.renewals.show', $renewal);
        }

        $token = $step === SubscriptionRenewal::APPROVER_SECOND
            ? $renewal->second_signed_token
            : $renewal->signed_token;

        return route('subscriptions.renewals.show.signed', [
            'renewal' => $renewal->id,
            'token'   => $token,
        ]);
    }

    private function approverStepFor(Request $request, SubscriptionRenewal $renewal, ?string $token): ?string
    {
        if ($token) {
            $step = $renewal->approverStepForToken($token);
            if ($step) {
                return $step;
            }
        }

        $user = $request->user();
        if ($user) {
            if ($renewal->second_approver_user_id && $user->id === $renewal->second_approver_user_id) {
                return SubscriptionRenewal::APPROVER_SECOND;
            }
            if ($renewal->approver_user_id && $user->id === $renewal->approver_user_id) {
                return SubscriptionRenewal::APPROVER_FIRST;
            }
        }

        return match ($renewal->status) {
            SubscriptionRenewal::STATUS_PENDING_SECOND => SubscriptionRenewal::APPROVER_SECOND,
            SubscriptionRenewal::STATUS_PENDING        => SubscriptionRenewal::APPROVER_FIRST,
            default                                    => null,
        };
    }

    private function authorizeView(Request $request, SubscriptionRenewal $renewal): void
    {
        $user = $request->user();

        if ($user) {
            if ($user->isAdmin()) return;
            if ($renewal->approver_user_id && $user->id === $renewal->approver_user_id) return;
            if ($renewal->second_approver_user_id && $user->id === $renewal->second_approver_user_id) return;
            if ($user->canView('subscriptions')) return;
        }

        $token = $request->query('token') ?: $request->route('token');
        if ($token && $renewal->approverStepForToken($token)) {
            return;
        }

        abort(403, 'You are not authorised to view this quotation.');
    }

    private function authorizeAction(Request $request, SubscriptionRenewal $renewal, ?string $token): string
    {
        if ($token) {
            $step = $renewal->approverStepForToken($token);
            if ($step) {
                return $step;
            }
        }

        $user = $request->user();
        if ($user) {
            if ($renewal->second_approver_user_id && $user->id === $renewal->second_approver_user_id) {
                return SubscriptionRenewal::APPROVER_SECOND;
            }
            if ($renewal->approver_user_id && $user->id === $renewal->approver_user_id) {
                return SubscriptionRenewal::APPROVER_FIRST;
            }
            if ($user->isAdmin()) {
                return match ($renewal->status) {
                    SubscriptionRenewal::STATUS_PENDING_SECOND => SubscriptionRenewal::APPROVER_SECOND,
                    default                                    => SubscriptionRenewal::APPROVER_FIRST,
                };
            }
        }

        abort(403, 'You are not authorised to act on this quotation.');
    }

    private function extendExpiry(?Carbon $current, ?string $renewalType): Carbon
    {
        $base = $current && $current->isFuture() ? $current : Carbon::today();

        return match ($renewalType) {
            'Monthly'        => $base->copy()->addMonth(),
            'Yearly'         => $base->copy()->addYear(),
            'Pay as you go'  => $base->copy()->addYear(),
            'One Time'       => $base->copy()->addYear(),
            default          => $base->copy()->addYear(),
        };
    }

    private function finalRecipients(SubscriptionRenewal $renewal): array
    {
        $emails = User::query()
            ->where('role', 'admin')
            ->pluck('email')
            ->all();

        $emails[] = $renewal->approver_email;
        if ($renewal->second_approver_email) {
            $emails[] = $renewal->second_approver_email;
        }

        return array_values(array_unique(array_filter(array_map('strtolower', $emails))));
    }
}
