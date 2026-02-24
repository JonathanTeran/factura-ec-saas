<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioWhatsAppService
{
    private string $sid;
    private string $token;
    private string $from;

    public function __construct()
    {
        $this->sid = config('services.twilio.sid', '');
        $this->token = config('services.twilio.token', '');
        $this->from = config('services.twilio.whatsapp_from', '');
    }

    /**
     * Enviar mensaje de WhatsApp
     */
    public function send(string $to, string $message, array $mediaUrls = []): bool
    {
        if (empty($this->sid) || empty($this->token)) {
            Log::warning('Twilio credentials not configured');
            return false;
        }

        try {
            $data = [
                'To' => "whatsapp:$to",
                'From' => "whatsapp:{$this->from}",
                'Body' => $message,
            ];

            if (!empty($mediaUrls)) {
                $data['MediaUrl'] = $mediaUrls[0]; // Twilio can take MediaUrl for attachments
            }

            $response = Http::asForm()
                ->withBasicAuth($this->sid, $this->token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json", $data);

            if ($response->successful()) {
                return true;
            }

            Log::error('Twilio WhatsApp error', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Twilio WhatsApp exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
