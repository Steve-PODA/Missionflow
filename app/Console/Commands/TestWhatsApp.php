<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class TestWhatsApp extends Command
{
    protected $signature   = 'whatsapp:test {phone : Numéro au format +24177123456}';
    protected $description = 'Envoie un message de test WhatsApp pour vérifier la configuration';

    public function handle(WhatsAppService $whatsapp): int
    {
        $phone = $this->argument('phone');
        $this->info("Envoi du message de test à {$phone}...");

        $ok = $whatsapp->sendText($phone, "✅ MissionFlow — connexion WhatsApp opérationnelle. Ce message confirme que votre configuration API est correcte.");

        if ($ok) {
            $this->info('Message envoyé avec succès.');
            return self::SUCCESS;
        }

        $this->error('Échec de l\'envoi. Vérifiez WHATSAPP_TOKEN et WHATSAPP_PHONE_NUMBER_ID dans .env et consultez storage/logs/laravel.log.');
        return self::FAILURE;
    }
}
