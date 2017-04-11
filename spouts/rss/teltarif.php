<?php

namespace spouts\rss;

/**
 * Plugin for fetching the news from Teltarif with the full text
 *
 * @copyright  Copyright (c) Martin Sauter (http://www.wirelessmoves.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Martin Sauter  <martin.sauter@wirelessmoves.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 */
class teltarif extends feed {
    /** @var string name of spout */
    public $name = 'News: Teltarif';

    /** @var string description of this source type */
    public $description = 'This feed fetches Telarif news with full content (not only the header as content)';

    /** @var array configurable parameters */
    public $params = [];

    /**
     * addresses of feeds for the sections
     */
    private $feedUrl = 'http://www.teltarif.de/feed/news/20.rss2';

    /**
     * htmLawed configuration
     */
    private $htmLawedConfig = [
        'abs_url' => 1,
        'base_url' => 'http://www.teltarif.de/',
        'comment' => 1,
        'safe' => 1,
    ];

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
        $start_marker = '<!-- Artikel -->';
        $end_marker = '<!-- NOPRINT Start -->';

        if ($this->items !== null && $this->valid()) {
            $originalContent = @file_get_contents($this->getLink());
            if ($originalContent) {
                $originalContent = mb_convert_encoding($originalContent, 'UTF-8', 'ISO-8859-1');

                // cut the article from the page
                $text_start_pos = strpos($originalContent, $start_marker);
                $text_end_pos = strrpos($originalContent, $end_marker);

                if ($text_start_pos !== false && $text_end_pos !== false) {
                    $content = substr(
                        $originalContent,
                        $text_start_pos + strlen($start_marker),
                        $text_end_pos - $text_start_pos - strlen($start_marker)
                    );

                    // remove most html coding and return result
                    return htmLawed($content, $this->htmLawedConfig);
                }
            }
        }

        return parent::getContent();
    }
}
