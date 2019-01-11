<?php

include_once 'aliyun-php-sdk-core/Config.php';
use Alidns\Request\V20150109 as Alidns;

$domain = 'www.cn';
$ip = getIP();
$rr = '@';
$key = 'xxxxxxxx';
$secret = 'yyyyyyyy';

$iClientProfile = DefaultProfile::getProfile("cn-hangzhou", $key, $secret);
$client = new DefaultAcsClient($iClientProfile);
$request = new Alidns\DescribeDomainRecordsRequest();
$request->setDomainName($domain);
$request->setType('A');

try {
    $response = $client->getAcsResponse($request);
} catch(ServerException $e) {
    print "Error: " . $e->getErrorCode() . " Message: " . $e->getMessage() . "\n";
} catch(ClientException $e) {
    print "Error: " . $e->getErrorCode() . " Message: " . $e->getMessage() . "\n";
}

$exist = 0;
date_default_timezone_set('PRC');
$log = date("Y-m-d H:i:s") . ' ';

foreach($response->DomainRecords->Record as $record) {
    if($record->RR == $rr) {
        $exist = 1;
        $record_id = $record->RecordId;

        if($record->Value == $ip) {
            $log .=  "{$rr}.{$domain} => {$ip} NOT CHANGE" . PHP_EOL;
            llog($log);
            die();
        }else{
            $log .=  "{$rr}.{$domain} => {$ip}, CHANGE FROM {$record->Value} ";
        }
    }
}

if($exist) {
    $request = new Alidns\UpdateDomainRecordRequest();
    $request->setRecordId($record_id);
    $request->setRR($rr);
    $request->setType("A");
    $request->setValue($ip);
}else{
    $log .= "{$rr}.{$domain} => {$ip} ADD ";
    $request = new Alidns\AddDomainRecordRequest();
    $request->setRR($rr);
    $request->setType("A");
    $request->setValue($ip);
    $request->setDomainName($domain);
}

try {
    $response = $client->getAcsResponse($request);
    $log .= "SUCCESS " . PHP_EOL;
} catch(ServerException $e) {
    $log .= "FAIL Error: " . $e->getErrorCode() . " Message: " . $e->getMessage() . PHP_EOL;
} catch(ClientException $e) {
    $log .= "FAIL Error: " . $e->getErrorCode() . " Message: " . $e->getMessage() . PHP_EOL;
}

llog($log);

function llog($log, $w = 1) {
    if($w) file_put_contents('./ddns.log', $log,FILE_APPEND);
    echo $log;
}

function getIp(){
    $externalContent = file_get_contents('http://checkip.dyndns.com/');
    preg_match('/Current IP Address: \[?([:.0-9a-fA-F]+)\]?/', $externalContent, $m);
    return $m[1];
}


?>