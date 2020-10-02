#!/usr/bin/env php
<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

if ($argc <= 1) {
    throw new InvalidArgumentException('You must specific at least one url');
}

$cookiejar = new GuzzleHttp\Cookie\CookieJar;
$http = new Client([
    'base_uri' => 'http://dl.free.fr/',
    'timeout'  => 15,
    'cookies'  => $cookiejar,
]);

$url = $argv[1];
$file_id = get_file_id($url);

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

    $recv_size = 0;
    while($filebody->eof() !== true) {
        $chunk = $filebody->read(1024 * 1e+6); // Equalivent to 1MB
        $recv_size += strlen($chunk);
        fwrite($output_file, $chunk);

        echo sprintf(
            "\e[K\r%10s of %s (%d%%)",
            format_filesize($recv_size),
            format_filesize($filesize),
            format_filesize($recv_size / $filesize * 100)
        );
    }
} catch (\GuzzleHttp\Exception\ClientException $e) {
    $code = $e->getResponse()->getStatusCode();
    echo "\e[31m[ERROR] Got status code: " . $code . PHP_EOL;
    switch ($code) {
        case 404:
            echo "Opss, its look like your file trying download is not available.\n";
        break;
    }
    echo "\e[0m";
}
