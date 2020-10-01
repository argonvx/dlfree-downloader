#!/usr/bin/env php
<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

function get_file_id($url) {
    if(preg_match('/^http:\/\/dl\.free\.fr\/(?:getfile\.pl\?file=\/(.+)|[\w]{1}(.+))$/', $url, $match)) {
        return $match[1]?: $match[2];
    } else {
        throw new Exception('Not dl.free.fr valid url');
    }
}

if ($argc <= 1) {
    throw new InvalidArgumentException('You must specific at least one url');
}

$cookiejar = new GuzzleHttp\Cookie\CookieJar;
$http = new Client([
    'base_uri' => 'http://dl.free.fr/',
    'timeout'  => 15,
    'cookies'  => $cookiejar
]);

$url = $argv[1];
$file_id = get_file_id($url);

$request_cookies = $http->post('getfile.pl', [
    'allow_redirects' => false,
    'form_params' => [
        'file' => '/' . $file_id,
        'send' => ''
    ]
]);

$location = $request_cookies->getHeader('location')[0];
$cookies  = $cookiejar->getCookieByName('getfile')->getValue();

$command = sprintf(
    'wget --quiet --show-progress --content-disposition --header="Cookie: getfile=%s;" %s',
    $cookies,
    $location
);

echo 'Spawning wGET to download file' . PHP_EOL;
system($command);