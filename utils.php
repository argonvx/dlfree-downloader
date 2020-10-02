<?php
function parse_disposition($content) {
    if (preg_match('/^filename=(?<filename>.+)$/', $content, $match)) {
        return $match['filename'];
    }
}

function format_filesize($size, $precision = 2) {
    static $units = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $step = 1024;
    $i = 0;
    while (($size / $step) > 0.9) {
        $size = $size / $step;
        $i++;
    }

    $round = round($size, $precision);
    return number_format($round, $precision) . $units[$i];
}

function get_file_id($url) {
    if(preg_match('/^http:\/\/dl\.free\.fr\/(?:getfile\.pl\?file=\/(.+)|[\w]{1}(.+))$/', $url, $match)) {
        return $match[1]?: $match[2];
    } else {
        throw new Exception('Not dl.free.fr valid url');
    }
}
