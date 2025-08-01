<?php

return [
    'base_url' => env('BETHA_BASE_URL', 'https://e-gov.betha.com.br'),
    'main_page' => env('BETHA_MAIN_PAGE', '/cdweb/03114-498/main.faces'),
    'selectors' => [
        'state' => '[name="mainForm:estados"]',
        'municipality' => '[name="mainForm:municipios"]',
        'submit_entity' => '#mainForm\:selecionar', 
        'cnd_menu_link' => 'a.boxMenu',
        'cnpj_mode_button' => 'a.btModo.cnpj',
        'cnpj_input' => '[name="mainForm:cnpj"]',
        'cnpj_submit_button' => '[name="mainForm:btCnpj"]',
        'emit_button' => 'img[alt="Emitir"]',
        'pdf_iframe' => 'iframe.fancybox-iframe',
        'download_button' => '#download',
    ],
    'values' => [
        'state' => ['22',  'SC - Santa Catarina'],
        'municipality' => ['177', 'Prefeitura Municipal de Navegantes'],
    ],
];