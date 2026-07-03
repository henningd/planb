<?php

namespace App\Mail;

use App\Models\Lead;
use App\Support\Marketing\Nis2QuickCheckCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Versendet die ausführliche NIS2-Auswertung als PDF an einen bestätigten
 * Lead. Wird erst nach abgeschlossenem Double-Opt-In ausgelöst.
 */
class Nis2QuickCheckReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Ihre persönliche NIS2-Auswertung'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.nis2-quick-check-report',
            with: [
                'lead' => $this->lead,
                'readiness' => $this->lead->readiness,
                'openRecommendations' => Nis2QuickCheckCatalog::openRecommendations($this->lead->answers ?? []),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->buildPdf(), 'nis2-quick-check-auswertung.pdf')
                ->withMime('application/pdf'),
        ];
    }

    public function buildPdf(): string
    {
        return Pdf::loadView('pdf.nis2-quick-check-report', [
            'lead' => $this->lead,
            'readiness' => $this->lead->readiness,
            'dimensions' => Nis2QuickCheckCatalog::dimensions(),
            'answers' => $this->lead->answers ?? [],
            'score' => $this->lead->score ?? 0,
            'maxScore' => Nis2QuickCheckCatalog::maxScore(),
        ])
            ->setPaper('a4')
            ->setOption(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true])
            ->output();
    }
}
