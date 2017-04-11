<?php

namespace spouts\rss;

/**
 * Spout for fetching images from given rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class images extends feed {
    /** @var string name of spout */
    public $name = 'RSS Feed Images';

    /** @var string description of this source type */
    public $description = 'Fetching images from given rss feed';

    /**
     * returns the thumbnail of this item (for multimedia feeds)
     *
     * @return mixed thumbnail data
     */
    public function getThumbnail() {
        if ($this->items === null || $this->valid() === false) {
            return '';
        }

        $item = current($this->items);

        // search enclosures (media tags)
        if (count(@$item->get_enclosures()) > 0) {
            // thumbnail given?
            if (@$item->get_enclosure(0)->get_thumbnail()) {
                return @$item->get_enclosure(0)->get_thumbnail();
            }

            // link given?
            elseif (@$item->get_enclosure(0)->get_link()) {
                return @$item->get_enclosure(0)->get_link();
            }

        // no enclosures: search image link in content
        } else {
            $image = $this->getImage(@$item->get_content());
            if ($image !== null) {
                return $image;
            }
        }

        return '';
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
