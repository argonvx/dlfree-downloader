#!/usr/bin/env php
<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

if ($argc <= 1) {
    throw new InvalidArgumentException('You must specific at least one url');
}

$url = $argv[1];
$proxy = $argv[2] ?? "";

$file_id = get_file_id($url);

$cookiejar = new GuzzleHttp\Cookie\CookieJar;
$http = new Client([
    'base_uri' => 'http://dl.free.fr/',
    'timeout'  => 15,
    'connect_timeout' => 15,
    'cookies'  => $cookiejar,
    'proxy' => $proxy
]);

try {
    $request_cookies = $http->post('getfile.pl', [
        'allow_redirects' => false,
        'form_params' => [
            'file' => '/' . $file_id,
            'send' => ''
        ]
    ]);

    $location = $request_cookies->getHeader('location')[0];
    $cookies  = $cookiejar->getCookieByName('getfile')->getValue();

    $getfile  = $http->get($location, [
        'stream' => true
    ]);

    $filebody = $getfile->getBody();
    $filesize = $getfile->getHeader('content-length')[0];
    $filename = parse_disposition($getfile->getHeader('content-disposition')[0]);

    // Start downloading file
    echo sprintf('[%s] Downloading %s', $file_id, $filename . PHP_EOL);
    $output_file = fopen(__DIR__ . '/' . $filename, 'wb');

    $start = microtime(true);
    $recv_size = 0;
    while($filebody->eof() !== true) {
        $chunk = $filebody->read(1024 * 1e+6); // Equalivent to 1MB
        $recv_size += strlen($chunk);
        $end = microtime(true);
        fwrite($output_file, $chunk);

        $downSpeed = calc_speed($start, $end, $recv_size);
        echo sprintf(
            "\e[K\r%10s of %s (%d%%) at %s/s",
            format_filesize($recv_size),
            format_filesize($filesize),
            format_filesize($recv_size / $filesize * 100),
            format_filesize($downSpeed)
        );
    }
} catch (\GuzzleHttp\Exception\ClientException $e) {
    $code = $e->getResponse()->getStatusCode();
    $msg  = $e->getResponse()->getReasonPhrase();
    echo sprintf("\e[31m[ERROR] Got status code: %d %s\n", $code, $msg);
    switch ($code) {
        case 404:
            echo "Opss, its look like your file trying download is not available.\n";
        break;
        default:
            echo $e->getMessage() . PHP_EOL;
        break;
    }
    echo "\e[0m";
} catch (\GuzzleHttp\Exception\ConnectException $e) {
    echo "\e[31m[ERROR] Connection error\n";
    echo $e->getMessage() . PHP_EOL;
    echo 'If you are using a proxy, make sure your proxy configured properly' . PHP_EOL;
    echo "\e[0m";
}
