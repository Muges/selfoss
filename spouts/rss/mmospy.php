<?php

namespace spouts\rss;

use SimplePie_IRI;

/**
 * Plugin for fetching the news from mmospy with the full text
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class mmospy extends feed {
    /** @var string name of spout */
    public $name = 'News: MMOspy';

    /** @var string description of this source type */
    public $description = 'This feed fetches the mmospy news with full content (not only the header as content)';

    /** @var array configurable parameters */
    public $params = [];

    /**
     * addresses of feeds for the sections
     */
    private $feedUrl = 'http://www.mmo-spy.de/misc.php?action=newsfeed';

    /**
     * loads content for given source
     *
     * @param array $params
     *
     * @return void
     */
    public function load(array $params) {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    /**
     * returns the xml feed url for the source
     *
     * @param array $params params for the source
     *
     * @return string url as xml
     */
    public function getXmlUrl(array $params) {
        return $this->feedUrl;
    }

    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if ($this->items !== null && $this->valid()) {
            $originalContent = file_get_contents($this->getLink());
            preg_match_all('|<div class="content">(.*?)</div>|ims', $originalContent, $matches, PREG_PATTERN_ORDER);
            if (is_array($matches) && is_array($matches[0]) && isset($matches[0][0])) {
                $content = utf8_encode($matches[0][0]);
                $content = preg_replace_callback(',<a([^>]+)href="([^>"\s]+)",i', function($matches) {
                    return '<a' . $matches[1] . 'href="' . SimplePie_IRI::absolutize('http://www.mmo-spy.de', $matches[2]) . '"';
                }, $content);
                $content = preg_replace_callback(',<img([^>]+)src="([^>"\s]+)",i', function($matches) {
                    return '<img' . $matches[1] . 'src="' . SimplePie_IRI::absolutize('http://www.mmo-spy.de', $matches[2]) . '"';
                }, $content);

                return $content;
            }
        }

        return parent::getContent();
    }
}
