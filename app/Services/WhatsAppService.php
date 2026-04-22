<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private string $token;
    private string $phoneNumberId;

    public function __construct()
    {
        $this->token         = config('services.whatsapp.token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->apiUrl        = "https://graph.facebook.com/v19.0/{$this->phoneNumberId}/messages";
    }

    /**
     * Envoie un message WhatsApp texte libre.
     * Fonctionne uniquement dans une fenêtre de 24h après un message entrant du destinataire.
     * Pour les alertes planifiées, utiliser sendTemplate().
     */
    public function sendText(string $to, string $message): bool
    {
        $to = $this->formatPhoneNumber($to);

        $response = Http::withToken($this->token)
            ->withOptions(['verify' => true])
            ->post($this->apiUrl, [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $message],
            ]);

        if ($response->failed()) {
            Log::error('WhatsApp sendText failed', [
                'to'       => $to,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Envoie un message via template approuvé Meta.
     * Obligatoire pour les messages initiés par l'entreprise (alertes planifiées).
     *
     * @param string $to           Numéro destinataire (ex: +24177123456)
     * @param string $templateName Nom du template approuvé dans Meta Business Manager
     * @param array  $components   Paramètres du template (variables {{1}}, {{2}}, etc.)
     */
    public function sendTemplate(string $to, string $templateName, array $components = []): bool
    {
        $to = $this->formatPhoneNumber($to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => ['code' => 'fr'],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        $response = Http::withToken($this->token)
            ->withOptions(['verify' => true])
            ->post($this->apiUrl, $payload);

        if ($response->failed()) {
            Log::error('WhatsApp sendTemplate failed', [
                'to'       => $to,
                'template' => $templateName,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Alerte "Rappel J-1" : mission demain.
     */
    public function sendMissionReminder(string $to, string $agentName, string $missionTitle, string $date, string $time, string $location): bool
    {
        return $this->sendTemplate($to, 'mission_reminder_v1', [
            [
                'type'       => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $agentName],
                    ['type' => 'text', 'text' => $missionTitle],
                    ['type' => 'text', 'text' => $date],
                    ['type' => 'text', 'text' => $time],
                    ['type' => 'text', 'text' => $location],
                ],
            ],
        ]);
    }

    /**
     * Alerte "Jour J" : mission aujourd'hui.
     */
    public function sendMissionDayAlert(string $to, string $agentName, string $missionTitle, string $time, string $location): bool
    {
        return $this->sendTemplate($to, 'mission_day_alert', [
            [
                'type'       => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $agentName],
                    ['type' => 'text', 'text' => $missionTitle],
                    ['type' => 'text', 'text' => $time],
                    ['type' => 'text', 'text' => $location],
                ],
            ],
        ]);
    }

    /**
     * Normalise le numéro : supprime espaces/tirets, s'assure qu'il commence par +.
     */
    private function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }
}
