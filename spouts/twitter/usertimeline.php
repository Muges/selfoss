<?php

namespace spouts\twitter;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Spout for fetching an rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class usertimeline extends \spouts\spout {
    /** @var string name of source */
    public $name = 'Twitter - User timeline';

    /** @var string description of this source type */
    public $description = 'The timeline of a given user';

    /** @var array configurable parameters */
    public $params = [
        'consumer_key' => [
            'title' => 'Consumer Key',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
        'consumer_secret' => [
            'title' => 'Consumer Secret',
            'type' => 'password',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
        'access_token' => [
            'title' => 'Access Token (optional)',
            'type' => 'text',
            'default' => '',
            'required' => false,
            'validation' => []
        ],
        'access_token_secret' => [
            'title' => 'Access Token Secret (optional)',
            'type' => 'password',
            'default' => '',
            'required' => false,
            'validation' => []
        ],
        'username' => [
            'title' => 'Username',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ]
    ];

    /** @var ?array current fetched items */
    protected $items = null;

    /** @var string URL of the source */
    protected $htmlUrl = '';

    //
    // Iterator Interface
    //

    /**
     * reset iterator
     *
     * @return void
     */
    public function rewind() {
        if ($this->items !== null) {
            reset($this->items);
        }
    }

    /**
     * receive current item
     *
     * @return \SimplePie_Item current item
     */
    public function current() {
        if ($this->items !== null) {
            return $this;
        }

        return false;
    }

    /**
     * receive key of current item
     *
     * @return mixed key of current item
     */
    public function key() {
        if ($this->items !== null) {
            return key($this->items);
        }

        return false;
    }

    /**
     * select next item
     *
     * @return \SimplePie_Item next item
     */
    public function next() {
        if ($this->items !== null) {
            next($this->items);
        }

        return $this;
    }

    /**
     * end reached
     *
     * @return bool false if end reached
     */
    public function valid() {
        if ($this->items !== null) {
            return current($this->items) !== false;
        }

        return false;
    }

    //
    // Source Methods
    //

    /**
     * loads content for given source
     * I supress all Warnings of SimplePie for ensuring
     * working plugin in PHP Strict mode
     *
     * @param array $params the params of this source
     *
     * @return void
     */
    public function load(array $params) {
        $access_token_used = !empty($params['access_token']) && !empty($params['access_token_secret']);
        $twitter = new TwitterOAuth($params['consumer_key'], $params['consumer_secret'], $access_token_used ? $params['access_token'] : null, $access_token_used ? $params['access_token_secret'] : null);
        $timeline = $twitter->get('statuses/user_timeline', ['screen_name' => $params['username'], 'include_rts' => 1, 'count' => 50]);

        if (isset($timeline->errors)) {
            $errors = '';

            foreach ($timeline->errors as $error) {
                $errors .= $error->message . "\n";
            }

            throw new \Exception($errors);
        }

        if (!is_array($timeline)) {
            throw new \Exception('invalid twitter response');
        }
        $this->items = $timeline;

        $this->htmlUrl = 'https://twitter.com/' . urlencode($params['username']);

        $this->spoutTitle = "@{$params['username']}";
    }

    /**
     * returns the global html url for the source
     *
     * @return string url as html
     */
    public function getHtmlUrl() {
        if (isset($this->htmlUrl)) {
            return $this->htmlUrl;
        }

        return null;
    }

    /**
     * returns an unique id for this item
     *
     * @return string id as hash
     */
    public function getId() {
        if ($this->items !== null) {
            return @current($this->items)->id_str;
        }

        return null;
    }

    /**
     * returns the current title as string
     *
     * @return string title
     */
    public function getTitle() {
        if ($this->items !== null) {
            $item = @current($this->items);
            $rt = '';
            if (isset($item->retweeted_status)) {
                $rt = ' (RT ' . $item->user->name . ')';
                $item = $item->retweeted_status;
            }
            $tweet = $item->user->name . $rt . ':<br>' . $this->formatLinks($item->text);

            return $tweet;
        }

        return null;
    }

    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        return '';
    }

    /**
     * returns the icon of this item
     *
     * @return string icon url
     */
    public function getIcon() {
        if ($this->items !== null) {
            $item = @current($this->items);
            if (isset($item->retweeted_status)) {
                $item = $item->retweeted_status;
            }

            return $item->user->profile_image_url;
        }

        return null;
    }

    /**
     * returns the link of this item
     *
     * @return string link
     */
    public function getLink() {
        if ($this->items !== null) {
            $item = @current($this->items);

            return 'https://twitter.com/' . $item->user->screen_name . '/status/' . $item->id_str;
        }

        return null;
    }

    /**
     * returns the thumbnail of this item (for multimedia feeds)
     *
     * @return mixed thumbnail data
     */
    public function getThumbnail() {
        if ($this->items !== null) {
            $item = current($this->items);
            if (isset($item->retweeted_status)) {
                $item = $item->retweeted_status;
            }
            if (isset($item->entities->media) && $item->entities->media[0]->type === 'photo') {
                return $item->entities->media[0]->media_url;
            }
        }

        return '';
    }

    /**
     * returns the date of this item
     *
     * @return string date
     */
    public function getDate() {
        if ($this->items !== null) {
            $date = date('Y-m-d H:i:s', strtotime(@current($this->items)->created_at));
        }
        if (strlen($date) === 0) {
            $date = date('Y-m-d H:i:s');
        }

        return $date;
    }

    /**
     * destroy the plugin (prevent memory issues)
     */
    public function destroy() {
        unset($this->items);
        $this->items = null;
    }

    /**
     * format links and emails as clickable
     *
     * @param string $text unformated text
     *
     * @return string formated text
     */
    public function formatLinks($text) {
        $text = htmlspecialchars($text);
        $text = preg_replace("/([\w-?&;#~=\.\/]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?))/i", '<a href="mailto:$1">$1</a>', $text);
        $text = str_replace('http://www.', 'www.', $text);
        $text = str_replace('www.', 'http://www.', $text);
        $text = preg_replace("/([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/i", '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $text);

        return $text;
    }
}
