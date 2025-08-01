<?php

namespace App\Services\Certidao;

use Symfony\Component\Panther\Client;

class BethaPortalNavigator
{
    private Client $client;
    private array $config;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->config = config('betha');
    }

    public function navigateAndFill(string $cnpj): void
    {
        $this->accessMainPage();
        $this->selectEntity();
        $this->navigateToCndForm();
        $this->fillCnpjAndSubmit($cnpj);
    }

    private function accessMainPage(): void
    {
        $this->client->request('GET', $this->config['base_url'] . $this->config['main_page']);
    }

    private function selectEntity(): void
    {
        $selectors = $this->config['selectors'];
        $values = $this->config['values'];

        $jsStateSelector = str_replace('\\', '\\\\', $selectors['state']);
        $jsMunicipalitySelector = str_replace('\\', '\\\\', $selectors['municipality']);

        $this->client->waitForVisibility($selectors['state']);
        $this->client->executeScript("document.querySelector('{$jsStateSelector}').value = 
            '{$values['state']}';");
        $this->client->executeScript("const s=document.querySelector('{$jsStateSelector}'); 
            const e=document.createEvent('HTMLEvents'); e.initEvent('change', true, false); s.dispatchEvent(e);");

        $this->client->waitForVisibility($selectors['municipality'] . ' option[value="' 
            . $values['municipality'] . '"]');
        $this->client->executeScript("document.querySelector('{$jsMunicipalitySelector}').value = 
            '{$values['municipality']}';");
        $this->client->executeScript("const s=document.querySelector('{$jsMunicipalitySelector}'); 
            const e=document.createEvent('HTMLEvents'); e.initEvent('change', true, false); s.dispatchEvent(e);");

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

    private function fillCnpjAndSubmit(string $cnpj): void
    {
        $selectors = $this->config['selectors'];
        $jsCnpjInputSelector = str_replace('\\', '\\\\', $selectors['cnpj_input']);

        $this->client->waitForVisibility($selectors['cnpj_mode_button']);
        $this->client->getCrawler()->filter($selectors['cnpj_mode_button'])->click();

        $this->client->waitForVisibility($selectors['cnpj_input']);
        $this->client->getCrawler()->filter($selectors['cnpj_input'])->sendKeys($cnpj);
        $this->client->getCrawler()->filter($selectors['cnpj_submit_button'])->click();
    }
}
