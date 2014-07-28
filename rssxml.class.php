<?php
/** @brief Rss parsing class builds on SimpleXMLElement to create from an RSS
 * srtucture from a http URL only.
 *
 * Construction results in a SimpleXMLElement tree. Methods returning a
 * JSON string of channel info and item content are provided.
 *
 * Throws exceptions as noted in constructor and methods.
 */
class RssXml {

    /** @brief Holds SimpleXMLElement structure;
     *
     *  @warning Can't override construct because SimpleXMLElement
     *  went final on it!
     */
    var $xmlElement = null;

    /** @brief Construction by http[s] url string only
     *
     *  Throws exceptions on invalid http[s] url, inability to read url
     *  and invalid XML content returned from url.
     */
    function __construct($url) {

        if (filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) === false
            || ( stripos($url,'http://') !== 0 && stripos($url,'https://') !== 0 )) {

            throw new Exception('Invalid url supplied "'.$url.'"');
        }

        libxml_use_internal_errors(true);
        libxml_use_internal_errors();
        try {
            $this->xmlElement = new SimpleXMLElement($url, LIBXML_NOCDATA, true);
        }
        catch (Exception $e) {
            throw new Exception($e->getmessage().' (URL:"'.$url.'")');
        }
        $oops = libxml_get_last_error();
        if ($oops === false)
            return;
        throw new Exception("libxml ERROR:".$oops->message.' (URL:"'.$url.'")');
    }

    /** Quick check that  rss XML was read
     */
    private function objectReady() {
        if (! ($this->xmlElement instanceof SimpleXMLElement)) {
            throw new Exception('Object not properly initialized!');
        }
    }

    /** @brief Fetch a select set of channel values as a json object.
     *
     *  Throws an exception if rss XML wasn't read
     *
     *  @returns A JSON encoded object for the channel
     */
    function channelInfo() {
        $this->objectReady();

        $x = new stdClass;

        if (($v = (string)$this->xmlElement->channel->title) <> '')
            $x->title = $v;
        else if (($v = (string)$this->xmlElement->channel->image->title) <> '')
            $x->title = $v; // use image title if no channel title

        if (($v = (string)$this->xmlElement->channel->link) <> '')
            $x->link = $v;
        else if (($v = (string)$this->xmlElement->channel->image->link) <> '')
            $x->link = $v; // use image link if no channel link

        if (($v = (string)$this->xmlElement->channel->description) <> '')
            $x->description = $v;

        if (($v = (string)$this->xmlElement->channel->image->url) <> '')
            $x->imgUrl = $v;
        else {
            /** @warning count is the only simple way to discover if element exists */
            if (count($this->xmlElement->channel->image)) {
                foreach($this->xmlElement->channel->image->attributes() as $k=>$v) {
                    if ($k === 'href') // alternate form is attr of image
                        $x->imgUrl = (string)$v;
                }       
            }
        }

        if (($v = (string)$this->xmlElement->channel->copyright) <> '')
            $x->copyright = $v;

        if (($v = (string)$this->xmlElement->channel->generator) <> '')
            $x->generator = $v;

        return $x;
    }

    /** Include title in JSON summary item */
    static $sumTitle = 0x01;

    /** Include link in JSON summary item */
    static $sumLink = 0x02;

    /** Include description in JSON summary item */
    static $sumDescription = 0x04;

    /** Include date in JSON summary item. Note date is reformatted for readibility I like. */
    static $sumDate = 0x08;

    /** Include img-url in JSON summary item.  N.B. this option also
     *  removes the image tag from the description. There is no
     *  attempt to properly use the media tag because it so rarly
     *  occurs.
     */
    static $sumImgUrl = 0x10;

    /** Include everything in JSON summary item */
    static $sumAll = 0x1F;


    /** @brief Summerize the RSS channel info as a JSON string.
     *
     *  Throws an exception if rss XML wasn't read
     *
     *  @param $options RssXml::sum... constant bits added to select
     *  options. Default sumAll
     *
     *  @returns A JSON encoded array of objects for each channel item
     *  as selected by options.
     */
    function itemSummary( $options = self::sumAll ) {

        $this->objectReady();

        $ret = array();

        foreach ($this->xmlElement->channel->item as $o) {

            /** Pull needed elements to form simple content structure
             *
             *  @warning casting is required here to keep from producing pointers
             *  to SimpleXMLElement objects
             */
            $x = new stdClass();

            if ($options & self::$sumTitle)
                $x->title = (string)$o->title;

            if ($options & self::$sumLink)
                $x->link = (string)$o->link;

            if ($options & self::$sumDate) {
                try { // reformat and validate time/date
                    $dateTime = new DateTime((string)$o->pubDate);
                    $x->niceDate = $dateTime->format('g:ia D j-M-Y');
                }
                catch (Exception $e) {
                    unset($x->niceDate); // chuck any invalid date/time
                }
            }

            $x->description = (string)$o->description; // assume wanted

            if ($options & self::$sumImgUrl) {
                // Pull img tag if there is one in the description
                //               1    2                    3                4        5
                if (preg_match('#(.*?)(\<img[^>]*\ src\=\")(https?\://[^"]+)([^>]*\>)(.*$)#ims',
                               $x->description, $matches)) {

                    $x->internal_img_url = $matches[3];
                    if ($options & self::$sumDescription)
                        $x->description = $matches[1].$matches[5]; // cut img from description
                }
            }

            if (! ($options & self::$sumDescription)) // description not wanted
                unset($x->description);


            $ret[] = $x; // build item structure list
        }

        return $ret;
    }
} // RssXml class
?>