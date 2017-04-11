<?php

namespace spouts\youtube;

/**
 * Spout for fetching a YouTube rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @copywork   Arndt Staudinger <info@clucose.com> April 2013
 */
class youtube extends \spouts\rss\feed {
    /** @var string name of source */
    public $name = 'YouTube Channel';

    /** @var string description of this source type */
    public $description = 'A YouTube channel as source';

    /** @var array configurable parameters */
    public $params = [
        'channel' => [
            'title' => 'Channel',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ]
    ];

    /**
     * loads content for given source
     *
     * @param array $params the params of this source
     *
     * @return void
     */
    public function load(array $params) {
        $url = $this->getXmlUrl($params);
        parent::load(['url' => $url]);
    }

    /**
     * returns the xml feed url for the source
     *
     * @param array $params params for the source
     *
     * @return string url as xml
     */
    public function getXmlUrl(array $params) {
        $channel = $params['channel'];
        if (preg_match('(^https?://www.youtube.com/channel/([a-zA-Z0-9_]+)$)', $params['channel'], $matched)) {
            $channel = $matched[1];
            $channel_type = 'channel_id';
        } elseif (preg_match('(^https?://www.youtube.com/([a-zA-Z0-9_]+)$)', $params['channel'], $matched)) {
            $channel = $matched[1];
            $channel_type = 'username';
        } else {
            $channel_type = 'username';
        }

        if ($channel_type === 'username') {
            return 'https://www.youtube.com/feeds/videos.xml?user=' . $channel;
        } else {
            return 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $channel;
        }
    }

    /**
     * returns the thumbnail of this item (for multimedia feeds)
     *
     * @return string|null thumbnail data
     */
    public function getThumbnail() {
        if ($this->items === false || $this->valid() === false) {
            return null;
        }

        $item = current($this->items);

        // search enclosures (media tags)
        if (count(@$item->get_enclosures()) > 0) {
            if (@$item->get_enclosure(0)->get_thumbnail()) {
                // thumbnail given
                return @$item->get_enclosure(0)->get_thumbnail();
            } elseif (@$item->get_enclosure(0)->get_link()) {
                // link given
                return @$item->get_enclosure(0)->get_link();
            }

        // no enclosures: search image link in content
        } else {
            $image = $this->getImage(@$item->get_content());
            if ($image !== null) {
                return $image;
            }
        }

        return null;
    }

    /**
     * taken from: http://zytzagoo.net/blog/2008/01/23/extracting-images-from-html-using-regular-expressions/
     * Searches for the first occurence of an html <img> element in a string
     * and extracts the src if it finds it. Returns null in case an <img>
     * element is not found.
     *
     * @param string $html An HTML string
     *
     * @return ?string content of the src attribute of the first image
     */
    private function getImage($html) {
        if (stripos($html, '<img') !== false) {
            $imgsrc_regex = '#<\s*img [^\>]*src\s*=\s*(["\'])(.*?)\1#im';
            preg_match($imgsrc_regex, $html, $matches);
            unset($imgsrc_regex);
            unset($html);
            if (is_array($matches) && !empty($matches)) {
                return $matches[2];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}
