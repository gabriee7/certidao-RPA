<?php

namespace App\Services\Certidao;

use Symfony\Component\Panther\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PdfDownloader
{
    private Client $client;
    private array $config;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->config = config('betha');
    }

    public function downloadAndSave(string $cnpj): string
    {
        $this->emitAndDownload();
        $downloadedFilePath = $this->waitForDownload();
        return $this->storeFile($downloadedFilePath, $cnpj);
    }

    private function emitAndDownload(): void
    {
        $selectors = $this->config['selectors'];
        $this->client->waitForVisibility($selectors['emit_button']);
        $this->client->getCrawler()->filter($selectors['emit_button'])->click();

        $this->client->waitFor($selectors['pdf_iframe']);
        $this->client->switchTo()->frame(0);

        $this->client->waitFor($selectors['download_button'], 20);
        $this->client->executeScript("document.querySelector('{$selectors['download_button']}').click();");

        $this->client->switchTo()->defaultContent();
    }

    private function waitForDownload(): string
    {
        $userProfile = getenv('USERPROFILE');
        $downloadPath = $userProfile . DIRECTORY_SEPARATOR . 'Downloads';
        $timeBeforeClick = time();
        $timeout = 40;

        $startTime = time();
        while (time() - $startTime < $timeout) {
            $files = File::glob($downloadPath . DIRECTORY_SEPARATOR . '*.pdf');
            foreach ($files as $file) {
                if (filemtime($file) >= $timeBeforeClick && 
                    filesize($file) > 0 && 
                    !str_ends_with($file, '.crdownload')
                ) {
                    return $file;
                }
            }
            sleep(1);
        }

        throw new RuntimeException("O download do PDF não foi concluído na 
            pasta padrão dentro do tempo limite de {$timeout} segundos.");
    }

    private function storeFile(string $filePath, string $cnpj): string
    {
        $pdfContent = File::get($filePath);
        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);
        $uniqueId = uniqid();
        $finalFileName = "CND_Navegantes_{$cleanCnpj}_{$uniqueId}.pdf";
        $finalPath = "certidoes/{$finalFileName}";

        Storage::disk('local')->put($finalPath, $pdfContent);
        File::delete($filePath);

        return storage_path('app/' . $finalPath);
    }
}
