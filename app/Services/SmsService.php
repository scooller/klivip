<?php

namespace App\Services;

use App\Enums\SmsStatus;
use App\Models\SentSms;
use App\Models\SmsTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function __construct(
        private readonly PreludeService $preludeService
    ) {}

    /**
     * Send an SMS using a template key.
     *
     * @param  string  $templateKey  e.g. 'coupons-received'
     * @param  string  $phone  E.164 phone number
     * @param  array<string, mixed>  $tokens  Token substitution values
     * @param  array{sendable?: Model|null, sent_by?: int|null, subject?: string|null}  $options
     */
    public function sendFromTemplate(string $templateKey, string $phone, array $tokens = [], array $options = []): SentSms
    {
        $template = SmsTemplate::withoutGlobalScope('active')
            ->byKey($templateKey)
            ->first();

        $body = $template
            ? $template->render(null, $tokens)
            : $this->renderRaw($templateKey, $tokens);

        $sender = $template?->sender_name ?? config('app.name', 'Klivip');

        $log = SentSms::create([
            'sms_template_id' => $template?->id,
            'to' => $phone,
            'from' => $sender,
            'subject' => $options['subject'] ?? null,
            'body' => $body,
            'status' => SmsStatus::Queued,
            'sent_by' => $options['sent_by'] ?? auth()->id(),
            'sendable_type' => $options['sendable']?->getMorphClass(),
            'sendable_id' => $options['sendable']?->getKey(),
        ]);

        try {
            $this->dispatchSms($phone, $body);

            $log->markAsSent();
        } catch (\Throwable $e) {
            $log->markAsFailed($e->getMessage());
            Log::error('SMS delivery failed', [
                'sent_sms_id' => $log->id,
                'template' => $templateKey,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Send a raw SMS message (no template).
     *
     * @param  array{sendable?: Model|null, sent_by?: int|null, subject?: string|null}  $options
     */
    public function sendRaw(string $phone, string $message, array $options = []): SentSms
    {
        $sender = config('app.name', 'Klivip');

        $log = SentSms::create([
            'to' => $phone,
            'from' => $sender,
            'subject' => $options['subject'] ?? null,
            'body' => $message,
            'status' => SmsStatus::Queued,
            'sent_by' => $options['sent_by'] ?? auth()->id(),
            'sendable_type' => $options['sendable']?->getMorphClass(),
            'sendable_id' => $options['sendable']?->getKey(),
        ]);

        try {
            $this->dispatchSms($phone, $message);

            $log->markAsSent();
        } catch (\Throwable $e) {
            $log->markAsFailed($e->getMessage());
            Log::error('SMS delivery failed (raw)', [
                'sent_sms_id' => $log->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Re-send an existing SentSms log entry.
     */
    public function resend(SentSms $original): SentSms
    {
        $log = SentSms::create([
            'sms_template_id' => $original->sms_template_id,
            'to' => $original->to,
            'from' => $original->from,
            'subject' => $original->subject,
            'body' => $original->body,
            'status' => SmsStatus::Queued,
            'sent_by' => auth()->id(),
            'sendable_type' => $original->sendable_type,
            'sendable_id' => $original->sendable_id,
            'metadata' => ['resent_from' => $original->id],
        ]);

        try {
            $this->dispatchSms($original->to, $original->body);

            $log->markAsSent();
        } catch (\Throwable $e) {
            $log->markAsFailed($e->getMessage());
        }

        return $log;
    }

    /**
     * Render a raw message with simple token substitution (used when no template found).
     *
     * @param  array<string, mixed>  $tokens
     */
    private function renderRaw(string $message, array $tokens = []): string
    {
        foreach ($tokens as $key => $value) {
            $message = str_replace('{{ '.$key.' }}', (string) $value, $message);
            $message = str_replace('{{'.$key.'}}', (string) $value, $message);
        }

        return $message;
    }

    /**
     * Dispatch the SMS through the configured provider.
     *
     * @throws \Throwable
     */
    private function dispatchSms(string $phone, string $message): void
    {
        // Attempt to send through Prelude (will log a warning if OTP-only)
        $result = $this->preludeService->sendSms($phone, $message);

        if ($result === false) {
            throw new \RuntimeException('El proveedor de SMS no pudo enviar el mensaje (posible API OTP-only).');
        }
    }
}
