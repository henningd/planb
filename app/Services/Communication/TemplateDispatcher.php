<?php

namespace App\Services\Communication;

use App\Enums\CommunicationChannel;
use App\Mail\CommunicationTemplateMail;
use App\Models\CommunicationDispatch;
use App\Models\CommunicationDispatchRecipient;
use App\Models\CommunicationTemplate;
use App\Models\Employee;
use App\Services\Sms\SmsGatewayContract;
use App\Support\TemplatePlaceholders;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Versendet Kommunikationsvorlagen (SMS via seven.io, E-Mail via Mailer) an
 * eine Auswahl von Mitarbeitern. Gemeinsame Versandlogik für die
 * Vorlagen-Seite und das Krisen-Cockpit.
 *
 * Empfänger sind ausschließlich Mitarbeiter mit gepflegter Mobilnummer (SMS)
 * bzw. E-Mail-Adresse. Platzhalter werden gegen die aktuelle Firma aufgelöst.
 */
class TemplateDispatcher
{
    public function __construct(private readonly SmsGatewayContract $sms) {}

    /**
     * @param  list<string>  $employeeIds
     * @return array{results: list<array{to: string, name: string, success: bool, error: ?string}>, sent: int, failed: int}
     */
    public function sendSms(CommunicationTemplate $template, array $employeeIds): array
    {
        $body = TemplatePlaceholders::resolve($template->body, Auth::user()?->currentCompany());

        $recipients = Employee::query()
            ->whereIn('id', $employeeIds)
            ->whereNotNull('mobile_phone')
            ->where('mobile_phone', '!=', '')
            ->get();

        $results = [];
        foreach ($recipients as $employee) {
            $result = $this->sms->send($employee->mobile_phone, $body);
            $results[] = [
                'to' => $result->to,
                'name' => $employee->fullName(),
                'success' => $result->success,
                'error' => $result->errorMessage,
            ];
        }

        $sent = collect($results)->where('success', true)->count();
        $failed = count($results) - $sent;

        if ($results !== []) {
            DB::table('audit_log_entries')->insert([
                'id' => (string) Str::uuid(),
                'company_id' => $template->company_id,
                'user_id' => Auth::id(),
                'entity_type' => 'CommunicationTemplate',
                'entity_id' => $template->id,
                'entity_label' => $template->name,
                'action' => 'sms.sent',
                'changes' => json_encode([
                    'sent' => $sent,
                    'failed' => $failed,
                    'recipients' => collect($results)->pluck('to')->all(),
                ]),
                'created_at' => now(),
            ]);
        }

        return ['results' => $results, 'sent' => $sent, 'failed' => $failed];
    }

    /**
     * @param  list<string>  $employeeIds
     * @return array{results: list<array{to: string, name: string, success: bool, error: ?string}>, sent: int, failed: int}
     */
    public function sendEmail(CommunicationTemplate $template, array $employeeIds): array
    {
        $company = Auth::user()?->currentCompany();
        $resolvedSubject = TemplatePlaceholders::resolve((string) $template->subject, $company);
        $resolvedBody = TemplatePlaceholders::resolve($template->body, $company);

        $recipients = Employee::query()
            ->whereIn('id', $employeeIds)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($recipients->isEmpty()) {
            return ['results' => [], 'sent' => 0, 'failed' => 0];
        }

        $dispatch = CommunicationDispatch::create([
            'communication_template_id' => $template->id,
            'dispatched_by_user_id' => Auth::id(),
            'channel' => CommunicationChannel::Email->value,
            'subject' => $resolvedSubject,
            'body' => $resolvedBody,
            'recipient_count' => $recipients->count(),
            'success_count' => 0,
            'failed_count' => 0,
            'dispatched_at' => now(),
        ]);

        $results = [];
        $sent = 0;
        $failed = 0;
        foreach ($recipients as $employee) {
            $success = true;
            $error = null;
            try {
                Mail::to($employee->email, $employee->fullName())
                    ->send(new CommunicationTemplateMail($resolvedSubject, $resolvedBody, $company?->name ?? config('app.name')));
            } catch (Throwable $e) {
                $success = false;
                $error = $e->getMessage();
            }

            CommunicationDispatchRecipient::create([
                'communication_dispatch_id' => $dispatch->id,
                'employee_id' => $employee->id,
                'email' => $employee->email,
                'name' => $employee->fullName(),
                'status' => $success ? 'sent' : 'failed',
                'error_message' => $error,
                'sent_at' => $success ? now() : null,
                'failed_at' => $success ? null : now(),
            ]);

            $results[] = [
                'to' => $employee->email,
                'name' => $employee->fullName(),
                'success' => $success,
                'error' => $error,
            ];

            $success ? $sent++ : $failed++;
        }

        $dispatch->update([
            'success_count' => $sent,
            'failed_count' => $failed,
        ]);

        return ['results' => $results, 'sent' => $sent, 'failed' => $failed];
    }
}
