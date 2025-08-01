<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Browser\BrowserClientFactory;
use App\Services\Certidao\BethaPortalNavigator;
use App\Services\Certidao\PdfDownloader;

class DownloadCertidaoNavegantesCommand extends Command
{
    protected $signature = 'app:download-certidao-navegantes {cnpj}';

    protected $description = 'Baixa a Certidão Negativa de Débitos (CND) 
        de CNPJ do município de Navegantes-SC.';

    public function handle()
    {
        $rawCnpj = $this->argument('cnpj');
        $cnpj = preg_replace('/\D/', '', $rawCnpj);

        if (!ValidatorHelper::isCnpj($cnpj)) {
            $this->error("CNPJ inválido: {$rawCnpj}");
            return Command::FAILURE;
        }

        $this->info("Iniciando automação para o CNPJ: {$cnpj}");

        $client = null;
        try {
            $client = BrowserClientFactory::create();

            $navigator = new BethaPortalNavigator($client);
            $navigator->navigateAndFill($cnpj);

            $downloader = new PdfDownloader($client);
            $savedPath = $downloader->downloadAndSave($cnpj);

            $this->info("Processo finalizado com sucesso!");
            $this->comment("Certidão salva em: {$savedPath}");
            
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Ocorreu um erro inesperado: " . $e->getMessage());
            
            if ($client) {
                $screenshotPath = storage_path('app/public/error_screenshots/error_' 
                    . preg_replace('/[^0-9]/', '', $cnpj) . '_' . date('Y-m-d_H-i-s') . '.png');
                $client->takeScreenshot($screenshotPath);
                $this->comment("Screenshot do erro salvo em: {$screenshotPath}");
            }

            return Command::FAILURE;

        } finally {
            if (isset($client)) {
                $client->quit();
            }
        }
    }
}
