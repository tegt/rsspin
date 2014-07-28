<?php
/** @file 
 *  Parse the contents of a given rss url
 */
$url = $argv[1];
if ($url == '') {
    $url = 'http://www.woot.com/blog/feed.rss';
}

require 'rssxml.class.php';

$it = new RssXml($url);

$chan = $it->channelInfo();
echo "===channel\n";
var_dump($chan);

echo "===items\n";
$summary = $it->itemSummary(RssXml::$sumTitle + RssXml::$sumDate + RssXml::$sumImgUrl);
var_dump($summary);
//var_dump($it->xmlElement);

$outName = $argv[2];
if ($outName <> '') {
    file_put_contents($outName, 'var items='.json_encode(summary).";\n");
}
?>