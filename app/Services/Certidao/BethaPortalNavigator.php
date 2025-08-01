<?php

namespace App\Services\Certidao;

use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\Exception;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use RuntimeException;

class BethaPortalNavigator
{
    private Client $client;
    private array $config;
    private string $cnpj;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->config = config('betha');
    }

    public function navigateAndFill(string $cnpj): void
    {
        $this->cnpj = $cnpj;
        $this->accessMainPage();
        $this->selectEntity();
        $this->navigateToCndForm();
        $this->fillCnpjAndSubmit();
    }

    private function accessMainPage(): void
    {
        $this->client->request('GET', $this->config['base_url'] . $this->config['main_page']);
    }

    private function selectEntity(): void
    {
        $selectors = $this->config['selectors'];
        $values = $this->config['values'];

        $stateValue = $values['state'][0];
        $stateLabel = $values['state'][1];

        $municipalityValue = $values['municipality'][0];
        $municipalityLabel = $values['municipality'][1];

        $jsStateSelector = str_replace('\\', '\\\\', $selectors['state']);
        $jsMunicipalitySelector = str_replace('\\', '\\\\', $selectors['municipality']);

        $this->client->waitForVisibility($selectors['state']);

        $this->client->executeScript("
            const s = document.querySelector('{$jsStateSelector}');
            if (s) {
                s.value = '{$stateValue}';
                const evt = document.createEvent('HTMLEvents');
                evt.initEvent('change', true, true);
                s.dispatchEvent(evt);
            }
        ");

        $this->client->waitForVisibility("{$selectors['municipality']} option[value='{$municipalityValue}']");

        $this->client->executeScript("
            const m = document.querySelector('{$jsMunicipalitySelector}');
            if (m) {
                m.value = '{$municipalityValue}';
                const evt = document.createEvent('HTMLEvents');
                evt.initEvent('change', true, true);
                m.dispatchEvent(evt);
            }
        ");

        $actualLabel = $this->client->executeScript("
            const m = document.querySelector('{$jsMunicipalitySelector}');
            const selected = m.options[m.selectedIndex];
            return selected ? selected.textContent.trim() : null;
        ");

        if ($actualLabel !== $municipalityLabel) {
            $this->takeScreenshotOnError("O município selecionado foi '{$actualLabel}', mas esperava-se '{$municipalityLabel}'");
        }

        $this->client->waitForVisibility($selectors['submit_entity']);
        $this->client->getCrawler()->filter($selectors['submit_entity'])->click();
    }

    private function navigateToCndForm(): void
    {
        $selectors = $this->config['selectors'];
        $this->client->waitForVisibility($selectors['cnd_menu_link']);
        $this->client->getCrawler()->filter($selectors['cnd_menu_link'])->reduce(fn($n) => 
            str_contains($n->text(), 'Certidão negativa de contribuinte'))->click();
    }

    private function fillCnpjAndSubmit(): void
    {
        $selectors = $this->config['selectors'];
        $jsCnpjInputSelector = str_replace('\\', '\\\\', $selectors['cnpj_input']);

        $this->client->waitForVisibility($selectors['cnpj_mode_button']);
        $this->client->getCrawler()->filter($selectors['cnpj_mode_button'])->click();

        $this->client->waitForVisibility($selectors['cnpj_input']);
        $this->client->getCrawler()->filter($selectors['cnpj_input'])->sendKeys($this->cnpj);
        $this->client->getCrawler()->filter($selectors['cnpj_submit_button'])->click();

        try {
            $this->client->waitForVisibility('#mainForm\\:master\\:messageSection\\:error', 5);

            $errorText = $this->client->getCrawler()->filter('#mainForm\\:master\\:messageSection\\:error')->text();
            throw new \RuntimeException("{$errorText}");

        } catch (TimeoutException $e) {
            throw $e;
        }
    }

    private function takeScreenshotOnError(string $message): void
    {
        $cnpj = preg_replace('/\D/', '', $this->cnpj ?? '');

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "error_{$cnpj}_{$timestamp}.png";
        $path = storage_path("app/private/error_screenshots/{$filename}");

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $this->client->takeScreenshot($path);

        throw new RuntimeException("{$message}. Screenshot do erro salvo em: {$path}");
    }
}
