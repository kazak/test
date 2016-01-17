<?php
/**
 * Created by PhpStorm.
 * User: dss
 * Date: 16.01.16
 * Time: 1:02
 */

/**
 * Class Crawler
 */
class Crawler
{
    /**
     * event hit parsing
     */
    const EVENT_HIT_CRAWL = 'event_hit_crawl';

    /**
     * event before parsing
     */
    const EVENT_BEFORE_CRAWL = 'event_before_crawl';

    /**
     * event after parsing
     */
    const EVENT_AFTER_CRAWL = 'event_after_crawl';

    /**
     * seen url
     * @var array
     */
    protected $_seen = [];

    /**
     * registered events
     * @var array
     */
    protected $_events = [];

    /**
     * start url
     * @var string
     */
    protected $_startUrl;

    /**
     * @param $url
     */
    public function crawl($url)
    {
        if (is_callable($this->_events[self::EVENT_BEFORE_CRAWL])) {
            call_user_func_array($this->_events[self::EVENT_BEFORE_CRAWL], [$url]);
        }

        $this->_startUrl = rtrim($url, '/') . '/';
        $this->_process($this->_startUrl);

        if (is_callable($this->_events[self::EVENT_AFTER_CRAWL])) {
            call_user_func_array($this->_events[self::EVENT_AFTER_CRAWL], [$url]);
        }
    }

    /**
     * @return array
     */
    public function getSeen()
    {
        return $this->_seen;
    }

    /**
     * @param $event
     * @param $cb
     * @throws Exception
     */
    public function on($event, $cb)
    {
        switch ($event) {
            case self::EVENT_HIT_CRAWL:
            case self::EVENT_BEFORE_CRAWL:
            case self::EVENT_AFTER_CRAWL:
                break;
            default:
                throw new Exception('Event "' . $event . '" not supported');
        }

        if (!is_callable($cb)) {
            throw new Exception('Function "$cb" not callable');
        }

        $this->_events[$event] = $cb;
    }

    /**
     * @param $href
     * @param $currentUrl
     * @return bool|string
     */
    public function buildUrl($href, $currentUrl)
    {
        if (substr($href, 0, 2) == '//') {
            return false;
        }

        $currentUrl      = rtrim($currentUrl, '/');
        $parseCurrentUrl = parse_url($currentUrl);
        $domainURL       = $parseCurrentUrl['scheme'] . '://' . $parseCurrentUrl['host'] . '/';
        $parts           = parse_url($href);

        if (!is_array($parts)) {
            return false;
        }

        $isExternal = false;

        if (isset($parts['scheme']) && (
                $parts['scheme'] == 'mailto' ||
                $parts['scheme'] == 'callto' ||
                $parts['scheme'] == 'tel' ||
                $parts['scheme'] == 'skype' ||
                $parts['scheme'] == 'javascript'
            )
        ) {
            return false;
        }

        if (isset($parts['host'])) {

            $isExternal = true;

        }

        if (isset($parts['path'])) {
            if (!empty($parts['path']) && $parts['path'][0] == '/') {

                if (mb_strlen($parts['path']) > 1) {

                    $parts['path'] = substr($parts['path'], 1);

                } else {

                    return $domainURL;
                }

            } else if (substr($parts['path'], 0, 2) == './') {

                $parts['path'] = substr($parts['path'], 2);

            } else if (substr($parts['path'], 0, 3) == '../') {

                $explodeParseCurrentUrl = explode('/', ltrim($parseCurrentUrl['path'], '/'));
                $explodeParseCurrentUrl = array_filter(array_reverse($explodeParseCurrentUrl));
                $countS = substr_count($parts['path'], '../');

                array_splice($explodeParseCurrentUrl, 0, $countS);

                return $domainURL . implode('/', array_reverse($explodeParseCurrentUrl)) . '/' . str_replace('../', '', $parts['path']);
            }
        }

        if (!$isExternal) {
            if (!isset($parts['host'])) {

                return $domainURL . $this->unparseUrl($parts);
            }

            return $currentUrl . '/' . $this->unparseUrl($parts);
        }

        if (isset($parts['host'])) {

            $parts['host'] = rtrim($parts['host'], '/') . '/';
        }

        return $this->unparseUrl($parts);
    }

    /**
     * @param array $parsed_url
     * @return string
     */
    public function unparseUrl(array $parsed_url)
    {
        $scheme   = isset($parsed_url['scheme'])   ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host'])     ? $parsed_url['host']           : '';
        $port     = isset($parsed_url['port'])     ? ':' . $parsed_url['port']     : '';
        $user     = isset($parsed_url['user'])     ? $parsed_url['user']           : '';
        $pass     = isset($parsed_url['pass'])     ? ':' . $parsed_url['pass']     : '';
        $pass     = ($user || $pass)               ? "$pass@"                      : '';
        $path     = isset($parsed_url['path'])     ? $parsed_url['path']           : '';
        $query    = isset($parsed_url['query'])    ? '?' . $parsed_url['query']    : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * get domain
     *
     * @param $url
     * @return bool
     */
    public function getDomain($url)
    {
        $pieces = parse_url($url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';

        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {

            return $regs['domain'];
        }

        return false;
    }

    /**
     * check to work url
     *
     * @param $url
     * @return bool
     */
    public function isCorrectPage($url)
    {
        $headers = @get_headers($url, 1);

        if ($headers && is_array($headers)) {

            if (isset($headers[0])) {
                if (trim(substr($headers[0], -6)) != '200 OK') {

                    return false;
                }
            }

            if (isset($headers["Content-Type"])) {
                if (stristr($headers["Content-Type"], 'text/html') === false) {

                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param $url
     */
    protected function _process($url)
    {
        if (isset($this->_seen[$url])) {

            return;
        }

        if (!$this->isCorrectPage($url) || $this->getDomain($url) != $this->getDomain($this->_startUrl)) {

            return;
        }

        $dom = new DOMDocument('1.0');
        @$dom->loadHTMLFile($url);

        $xpath    = new \DOMXPath($dom);
        $body     = $xpath->query('/html/body');
        $pageHash = md5($dom->saveXml($body->item(0)));

        if (in_array($pageHash, $this->_seen)) {

            return;
        }

        $this->_seen[$url] = $pageHash;

        if (is_callable($this->_events[self::EVENT_HIT_CRAWL])) {

            call_user_func_array($this->_events[self::EVENT_HIT_CRAWL], [$url, $dom]);
        }

        $anchors = $dom->getElementsByTagName('a');

        foreach ($anchors as $element) {

            $href = $element->getAttribute('href');
            $href = $this->buildUrl($href, $url);

            if ($href) {

                $this->_process($href);
            }
        }
    }
}