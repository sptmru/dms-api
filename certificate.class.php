<?php

define('FPDF_FONTPATH',__DIR__ . '/fonts');
require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use setasign\Fpdi\Fpdi;


class Certificate {
  private $log;
  private $pdo;
  private $application_file;

  public function __construct(array $config) {
    $this->log = new Logger('logfile');
    $this->log->pushHandler(new StreamHandler($config['logFile'], $config['logLevel']));

    $mysql_dsn = "mysql:host=". $config['mysql_host'] . ";dbname=" . $config['mysql_db'] . ";charset=" . $config['mysql_charset'];
    $this->pdo = new PDO($mysql_dsn, $config['mysql_user'], $config['mysql_password'], $config['mysql_opt']);

    $this->application_file = $config['application_file'];
    if (!file_exists($this->application_file)) {
      throw new Exception("Application file $this->application_file does not exist!");
    }
  }

  public function create(SimpleXMLElement $data): string {
    $current_date = new DateTime();
    $person_id = $this->add_person($data);
    $policy_number = $this->add_policy($person_id, $current_date, $data);
    
    $pdf = $this->generate_application($data, $policy_number, $current_date);
    return '
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://example.org/publishing" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:soap-enc="http://schemas.xmlsoap.org/soap/encoding/">
    <soapenv:Body>
    <tns:obtainCertificateResponse>
    <tns:result code="OK"/>
    <tns:cert number="'.$policy_number.'">
    <s0:certFile>'.$pdf.'
    </s0:certFile>
    </soapenv:Body>
    </soapenv:Envelope>
    ';
  }

  public function update(SimpleXMLElement $data): string {
    $policy_data = $data->soapenvBody->mmcsetPaymentFlagRequest->mmcpaidCertificate;
  
    $updated_policy_data = [
      'paymentId' => $policy_data->attributes()->paymentId,
      'paymentDate' => $policy_data->attributes()->paymentDate,
      'number' => $policy_data->attributes()->number,
    ];
  
    $sql = "UPDATE policy SET paymentId=:paymentId, paymentDate=:paymentDate WHERE number=:number";
    $stmt = $this->pdo->prepare($sql);
    $result = $stmt->execute($updated_policy_data);
    return '
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://example.org/publishing" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:soap-enc="http://schemas.xmlsoap.org/soap/encoding/">
    <soapenv:Body>
    <tns:setPaymentFlagResponse>
    <tns:result code="OK"/>
    </tns:setPaymentFlagResponse>
    </soapenv:Body>
    </soapenv:Envelope>
    ';
  }

  public function healthcheck(SimpleXMLElement $data): string {
    $link = $data->attributes()["xmlnsmmc"];
    $xml = '
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:soap-enc="http://schemas.xmlsoap.org/soap/encoding/">
    <soapenv:Body>
    <checkResponse xmlns="'.$link.'">
    <xsd:return>OK</xsd:return>
    </checkResponse>
    </soapenv:Body>
    </soapenv:Envelope>
    ';
  
    $xml = simplexml_load_string($xml, NULL, NULL, "http://schemas.xmlsoap.org/soap/envelope/");
    $xml = str_replace('<?xml version="1.0"?>', '', $xml->asXML());
    return $xml;
  }

  private function generate_application(SimpleXMLElement $data, string $policy_number, DateTime $current_date): string {
    if(!$policy_number) {
      return false;
    }
  
    $person = $data->soapenvBody->mmcobtainCertificateRequest->mmcperson;
    $passport = $data->soapenvBody->mmcobtainCertificateRequest->mmcpassport;
  
    $current_date_format = $this->fix_encoding($current_date->format("d.m.Y г."));
  
    $name = $this->fix_encoding($person->attributes()->lastName . " " . $person->attributes()->name . " " . $person->attributes()->secondName);
    $date_of_birth = DateTime::createFromFormat("Y-m-d", $person->attributes()->dateOfBirth);
    $address = $this->fix_encoding($person->attributes()->address);
    $phone = $person->attributes()->phone;
  
    $document_name = $this->fix_encoding("паспорт");
    $passport_number = $passport->attributes()->number;
    $passport_issuer = $this->fix_encoding($passport->attributes()->issuedBy);
    $passport_issue_date = DateTime::createFromFormat("Y-m-d", $passport->attributes()->issueDate);
  
    $application_template_file = $this->application_file;
    $application = new Fpdi();
    $application->AddFont('times_new_roman','','times_new_roman.php');
    $application->addPage();
    $application->setSourceFile($application_template_file);
  
    // $tplId = $application->importPage(1);
    // $application->useTemplate($tplId, 0, 0);
  
    $application->SetFont('times_new_roman', '', '13', true); 
    $application->SetTextColor(0,0,0);
  
    $application->SetXY(135, 63);
    $application->Write(0, $current_date_format);
  
    $application->SetXY(168, 63);
    $application->Write(0, $policy_number);
  
    $application->SetXY(18, 77);
    $application->Write(0, $name);
  
    $application->SetXY(12, 88);
    $application->Write(0, $date_of_birth->format("d"));
  
    $application->SetXY(20, 88);
    $application->Write(0, $this->month($date_of_birth->format("m")));
  
    $application->SetXY(38, 88);
    $application->Write(0, $date_of_birth->format("Y"));
  
    $application->SetXY(12, 95);
    $application->Write(0, $address);
  
    $application->SetXY(93, 101);
    $application->Write(0, $document_name);
  
    $application->SetXY(85, 108);
    $application->Write(0, $passport_number);
  
    $application->SetXY(58, 115);
    $application->Write(0, $passport_issuer);
  
    $application->SetXY(35, 122);
    $application->Write(0, $this->fix_encoding($passport_issue_date->format("d.m.Y г.")));
  
    $application->SetXY(118, 122);
    $application->Write(0, $phone);
  
    $application->SetXY(60, 148);
    $application->Write(0, $current_date_format);
  
    $application->SetXY(100, 148);
    $application->Write(0, $policy_number);
  
    $application->SetXY(12, 180);
    $application->Write(0, $name);
  
    $application->SetXY(32, 202);
    $application->Write(0, $current_date->format("d"));
  
    $application->SetXY(47, 202);
    $application->Write(0, $this->month($current_date->format("m")));
  
    $application->SetXY(90, 202);
    $application->Write(0, substr($current_date->format("Y"), -2));
  
    return base64_encode($application->Output('', 'S'));
  }

  private function add_person(SimpleXMLElement $data): string {
    $this->log->debug("Adding person");
    $person = $data->soapenvBody->mmcobtainCertificateRequest->mmcperson;
    $passport = $data->soapenvBody->mmcobtainCertificateRequest->mmcpassport;
    $personData = [
      'name' => $person->attributes()->name,
      'secondName' => $person->attributes()->secondName,
      'lastName' => $person->attributes()->lastName,
      'name_lat' => $person->attributes()->name_lat,
      'secondName_lat' => $person->attributes()->secondName_lat,
      'lastName_lat' => $person->attributes()->lastName_lat,
      'dateOfBirth' => $person->attributes()->dateOfBirth,
      'countryOfBirth' => $person->attributes()->countryOfBirth,
      'placeOfBirth' => $person->attributes()->placeOfBirth,
      'phone' => $person->attributes()->phone,
      'address' => $person->attributes()->address,
      'registrationDate' => $person->attributes()->registrationDate,
      'sex' => $person->attributes()->sex,
      'citizenship' => $person->attributes()->citizenship,
      'bsoNumber' => $person->attributes()->bsoNumber,
      'passportNumber' => $passport->attributes()->number,
      'passportIssueDate' => $passport->attributes()->issueDate,
      'passportIssuedBy' => $passport->attributes()->issuedBy,
    ];
    $column_list = join(',', array_keys($personData));
    $values = join(',', array_map(function($col) { return ":$col"; }, array_keys($personData)));
    $sql = "INSERT INTO person ($column_list) VALUES ($values)";
    $stmt= $this->pdo->prepare($sql);
    $result = $stmt->execute($personData);
    return $result ? $this->pdo->lastInsertId() : '';
  }

  private function add_policy(string $person_id, DateTime $date, SimpleXMLElement $data) {
    $this->log->debug("Adding policy for person $person_id");
    if ($person_id === FALSE) {
      return FALSE;
    }
    $policy_number = $this->generate_policy_number($date);
    $policy_data = [
      'applicationId' => $data->soapenvBody->mmcobtainCertificateRequest->attributes()->applicationId,
      'number' => $policy_number,
      'personId' => $person_id,
    ];
    $column_list = join(',', array_keys($policy_data));
    $values = join(',', array_map(function($col) { return ":$col"; }, array_keys($policy_data)));
    $sql = "INSERT INTO policy ($column_list) VALUES ($values)";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($policy_data);
  
    return $policy_number;
  }

  public function parse_payload(string $payload): ?SimpleXMLElement {
    $this->log->debug("Parsing payload: $payload");
    
    $xml = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $payload);
    $xml = trim(preg_replace("/\r|\n/", '', $xml));
    $xml = str_replace("xmlns:", "xmlns", $xml);
    return simplexml_load_string($xml);
  }
  
  private function generate_policy_number(DateTime $date): string {
    $this->log->debug("Generating policy number");
    $quantity_of_policies = $this->pdo->query('select count(*) from policy where date(creationDate) = CURDATE()')->fetchColumn(); 
    $number = $quantity_of_policies + 1;
    return $date->format('Y md') . '-' . $number;
  }

  private function month(string $month): string {
    $months = [];
    $months[1] = 'января';
    $months[2] = 'февраля';
    $months[3] = 'марта';
    $months[4] = 'апреля';
    $months[5] = 'мая';
    $months[6] = 'июня';
    $months[7] = 'июля';
    $months[8] = 'августа';
    $months[9] = 'сентября';
    $months[10] = 'октября';
    $months[11] = 'ноября';
    $months[12]= 'декабря';
    if (array_key_exists(intval($month), $months)) {
      return $this->fix_encoding($months[$month]);
    } else {
      $this->log->error("Incorrect month number: $month");
      return "";
    }
  }

  private function fix_encoding(string $string): string {
    return iconv('utf-8', 'windows-1251', $string);
  }
}