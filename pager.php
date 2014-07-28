<?php
/** @file 
 *  Parse the contents of a given rss url
 */

$url = 'http://www.woot.com/blog/feed.rs';
if ($_POST['pageurl'] <> '')
    $url = $_POST['pageurl'];

file_put_contents('xx', print_r($_POST, true));

require 'rssxml.class.php';

$response = new stdClass;
$response->error = '';

error_reporting(0);

try {
    $it = new RssXml($url);

    $channelInfo = $it->channelInfo();
    $response->channel = $channelInfo;

    $rssFlags = 0;
    foreach ($_POST as $k=>$v) {
        if (substr($k,0,3) == 'sum') {
            $rssFlags  += RssXml::$$k;
        }
    }
    $itemSummary = $it->itemSummary(RssXml::$sumTitle + $rssFlags);
    $response->items = $itemSummary;
}
catch (Exception $e) {
    $response->error = $e->getMessage();
}

//file_put_contents('je', $json_encode($itemSummary));
header('Content-type: application/json');
echo json_encode($response);

?>