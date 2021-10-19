<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/certificate.class.php';

$config = include (__DIR__ . '/config.php');

$certificate = new Certificate($config);

$request_payload = file_get_contents('php://input');
$parsed_payload = $certificate->parse_payload($request_payload);

if ($parsed_payload->soapenvBody->mmcobtainCertificateRequest) {
  echo $certificate->create($parsed_payload);
}

if ($parsed_payload->soapenvBody->mmcsetPaymentFlagRequest) {
  echo $certificate->update($parsed_payload);
}

if ($parsed_payload->soapenvBody->mmccheckRequest) {
  echo $certificate->healthcheck($parsed_payload);
}
