<?php
// Bu dosyayı config.php olarak kopyalayın. Gerçek config.php GitHub'a yüklenmez.
return [
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => '',
    'db_user' => '',
    'db_password' => '',
    'public_origin' => 'https://sencanhairstudio.com',
    'allowed_origins' => ['https://www.sencanhairstudio.com'],
    'cookie_secure' => true,
    // İlk kurulum için kendiniz en az 24 karakterlik rastgele bir anahtar seçin.
    'install_token' => '',
    'backup_keep_days' => 30,
    'mail_enabled' => false,
    'resend_api_key' => '',
    'mail_from' => '',
    'mail_to' => [],
];
