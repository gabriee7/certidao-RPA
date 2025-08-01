<?php

namespace App\Services\Browser;

use Symfony\Component\Panther\Client;
use Illuminate\Support\Facades\File;

class BrowserClientFactory
{
    public static function create(): Client
    {
        $driverPath = base_path(env('CHROMEDRIVER_EXECUTABLE', 'drivers\\chromedriver.exe'));

        if (!File::exists($driverPath)) {
            throw new \RuntimeException("Chromedriver não encontrado em: {$driverPath}. 
                Execute 'vendor\\bin\\bdi detect drivers'.");
        }

        $chromeOptions = [
            '--disable-gpu',
            '--no-sandbox',
            '--start-maximized',
            '--disable-blink-features=AutomationControlled',
            '--log-level=3'
        ];

        return Client::createChromeClient($driverPath, $chromeOptions);
    }
}
