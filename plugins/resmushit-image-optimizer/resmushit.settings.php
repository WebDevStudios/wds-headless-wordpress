<?php

define('RESMUSHIT_ENDPOINT', 'http://api.resmush.it/');
define('RESMUSHIT_VERSION', '0.3.11');
define('RESMUSHIT_DEFAULT_QLTY', '92');
define('RESMUSHIT_TIMEOUT', '10');
define('RESMUSHIT_LOGS_PATH', 'resmushit.log');
define('RESMUSHIT_LOGS_MAX_FILESIZE', '102400');
define('RESMUSHIT_NEWSFEED', 'https://feed.resmush.it/');
define('RESMUSHIT_BASE_URL', plugin_dir_url( __FILE__ ));
define('RESMUSHIT_CRON_FREQUENCY', 600);
define('RESMUSHIT_CRON_TIMEOUT', 60);

global $is_cron;