<?php
/** @file 
 *  Parse the contents of a given rss url
 */

$url = 'http://www.woot.com/blog/feed.rss';

require 'rssxml.class.php';

try {
    $it = new RssXml($url);
    $chanInfo = $it->channelInfo();
    $itemSummary = $it->itemSummary(RssXml::$sumTitle + RssXml::$sumImgUrl + RSSXml::$sumDate);
}
catch (Exception $e) {
}

header('Content-type: application/javascript');
echo 'items = '.json_encode($itemSummary).";\n";
?>