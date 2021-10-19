<?php

require __DIR__ . '/vendor/autoload.php';
use Monolog\Logger;

return array(
  'logFile' => './parser.log',
  'logLevel' => Logger::DEBUG,

  'mysql_host' => '127.0.0.1',
  'mysql_port' => 3306,
  'mysql_user' => 'insurance',
  'mysql_password' => 'nsl00kup',
  'mysql_charset' => 'utf8',
  'mysql_db' => 'insurance',
  'mysql_opt' => [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ],

  'application_file' => __DIR__ . '/application.pdf',
);