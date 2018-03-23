<?php
define('ENVIRONNEMENT', 'prod');//prod

//////////////////////////////CACHE
define('CACHE_HTLM', false);
define('CACHE_MODEL', false);//'disque' ou 'redis' ou false

if ('disque' === 'CACHE_MODEL'){//set path to store cache on HardDrive
    define('CACHE_MODEL_DISK_PATH', 'cache' . 'DIRECTORY_SEPARATOR');
} else {
    define('CACHE_MODEL_DISK_PATH', '');
}

//////////////////////////////MYSQL
$BaseName = 'sym4';
$BaseHost = 'symfony4_mariaDB';
//access
define('BASE_ADR', 'mysql:host=' . $BaseHost . '; dbname=' . $BaseName);
define('BASE_LOG', 'root');
define('BASE_PWD', '123456');

//ending de contraintes (.sql)
define ('UNIQUE_ENDING', '_unik');
define ('FK_ENDING', '_fokke');

//////////////////////////////REDIS
define('REDIS_PHP', 'symfony4_redis');
