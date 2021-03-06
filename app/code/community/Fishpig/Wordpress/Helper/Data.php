<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Helper_Data extends Fishpig_Wordpress_Helper_Abstract
{
    /**
     * Retrieve the top link URL
     *
     * @return false|string
     */
    public function getTopLinkUrl()
    {
        try {
            if ($this->isEnabled()) {
                if ($this->isFullyIntegrated()) {
                    if ($this->_isCached('toplink_url')) {
                        return $this->_cached('toplink_url');
                    }

                    $transport = new Varien_Object(array('toplink_url' => $this->getUrl()));

                    Mage::dispatchEvent('wordpress_get_toplink_url', array('transport' => $transport));

                    $this->_cache('toplink_url', $transport->getToplinkUrl());

                    return $transport->getToplinkUrl();
                }

                return $this->getWpOption('home');
            }
        } catch (Exception $e) {
            $this->log('Magento & WordPress are not correctly integrated (see entry below).');
            $this->log($e->getMessage());
        }

        return false;
    }

    /**
     * Retrieve the position for the top link
     *
     * @return false|int
     */
    public function getTopLinkPosition()
    {
        if ($this->isEnabled()) {
            return (int) Mage::getStoreConfig('wordpress/toplink/position');
        }

        return false;
    }

    /**
     * Returns the label for the top link
     * This is also used for the breadcrumb
     *
     * @return false|string
     */
    public function getTopLinkLabel()
    {
        if ($this->isEnabled()) {
            if ($this->_isCached('toplink_label')) {
                return $this->_cached('toplink_label');
            }

            $transport = new Varien_Object(array('toplink_label' => Mage::getStoreConfig('wordpress/toplink/label')));

            Mage::dispatchEvent('wordpress_get_toplink_label', array('transport' => $transport));

            $this->_cache('toplink_label', $transport->getToplinkLabel());

            return $transport->getToplinkLabel();
        }

        return false;
    }

    /**
     * Returns the pretty version of the blog route
     * This is deprecated. Instead, use self::getTopLinkLabel
     *
     * @return false|string
     */
    public function getPrettyBlogRoute()
    {
        return $this->getTopLinkLabel();
    }

    /**
     * Returns the URL Wordpress is installed on
     *
     * @param string $extra
     * @return string
     */
    public function getBaseUrl($extra = '')
    {
        return rtrim($this->getWpOption('siteurl'), '/') . '/' . $extra;
    }

    /**
     * Get Wordpress Admin URL
     *
     */
    public function getAdminUrl($extra = null)
    {
        return $this->getBaseUrl('wp-admin/' . $extra);
    }

    /**
     * Returns the given string prefixed with the Wordpress table prefix
     *
     * @return string
     */
    public function getTableName($table)
    {
        return Mage::helper('wordpress/database')->getTableName($table);
    }

    /**
     * Determine whether the module is enabled
     * This can be changed by going to System > Configuration > Advanced
     *
     * @return bool
     */
    public function isEnabled()
    {
        return Mage::helper('wordpress/config')->getConfigFlag('wordpress/module/enabled')
        && !Mage::getStoreConfig('advanced/modules_disable_output/Fishpig_Wordpress');
    }

    /**
     * Formats a Wordpress date string
     *
     */
    public function formatDate($date, $format = null, $f = false)
    {
        if ($format == null) {
            $format = $this->getDefaultDateFormat();
        }

        /**
         * This allows you to translate month names rather than whole date strings
         * eg. "March","Mars"
         *
         */
        $len = strlen($format);
        $out = '';

        for ($i = 0; $i < $len; $i++) {
            $out .= $this->__(Mage::getModel('core/date')->date($format[$i], strtotime($date)));
        }

        return $out;
    }

    /**
     * Formats a Wordpress date string
     *
     */
    public function formatTime($time, $format = null)
    {
        if ($format == null) {
            $format = $this->getDefaultTimeFormat();
        }

        return $this->formatDate($time, $format);
    }

    /**
     * Split a date by spaces and translate
     *
     * @param string $date
     * @param string $splitter = ' '
     * @return string
     */
    public function translateDate($date, $splitter = ' ')
    {
        $dates = explode($splitter, $date);

        foreach ($dates as $it => $part) {
            $dates[$it] = $this->__($part);
        }

        return implode($splitter, $dates);
    }

    /**
     * Return the default date formatting
     *
     */
    public function getDefaultDateFormat()
    {
        return $this->getWpOption('date_format', 'F jS, Y');
    }

    /**
     * Return the default time formatting
     *
     */
    public function getDefaultTimeFormat()
    {
        return $this->getWpOption('time_format', 'g:ia');
    }

    /**
     * Determine whether a WordPress plugin is enabled in the WP admin
     *
     * @param string $name
     * @param bool $format
     * @return bool
     */
    public function isPluginEnabled($name)
    {
        $name    = Mage::getSingleton('catalog/product_url')->formatUrlKey($name);
        $plugins = array();

        if ($plugins = $this->getWpOption('active_plugins')) {
            $plugins = unserialize($plugins);
        }

        if ($this->isWordPressMU() && Mage::helper('wpmultisite')->canRun()) {
            if ($networkPlugins = Mage::helper('wpmultisite')->getWpSiteOption('active_sitewide_plugins')) {
                $plugins += (array) unserialize($networkPlugins);
            }
        }

        if ($plugins) {
            foreach ($plugins as $a => $b) {
                if (strpos($a . '-' . $b, $name) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine whether to force single store
     *
     * @return bool
     */
    public function forceSingleStore()
    {
        return Mage::getStoreConfigFlag('wordpress/integration/force_single_store');
    }

    /**
     * Determine whether Fishpig_WordpressMu can run
     *
     * @return bool
     */
    public function isWordPressMU()
    {
        if (!$this->_isCached('is_wpmu')) {
            $this->_cache('is_wpmu', false);

            if ($this->isWordPressMUInstalled()) {
                $this->_cache('is_wpmu', Mage::helper('wpmultisite')->canRun());
            }
        }

        return $this->_cached('is_wpmu');
    }

    /**
     * Determine whether Fishpig_WordpressMu is installed
     *
     * @return bool
     */
    public function isWordPressMUInstalled()
    {
        if (!$this->_isCached('is_wpmu_installed')) {
            $config = Mage::getConfig();

            $isInstalled = (string) $config->getNode('modules/Fishpig_Wordpress_Addon_Multisite/active') === 'true'
            || (string) $config->getNode('modules/Fishpig_WordpressMu/active') === 'true';

            $this->_cache('is_wpmu_installed', $isInstalled);
        }

        return $this->_cached('is_wpmu_installed');
    }

    /**
     * Retrieve the upload URL
     *
     * @return string
     */
    public function getFileUploadUrl()
    {
        $url = $this->getWpOption('fileupload_url');

        if (!$url) {
            foreach (array('upload_url_path', 'upload_path') as $config) {
                if ($value = $this->getWpOption($config)) {
                    if (strpos($value, 'http') === false) {
                        if (substr($value, 0, 1) !== '/') {
                            $url = $this->getBaseUrl($value);
                        }
                    } else {
                        $url = $value;
                    }

                    break;
                }
            }

            if (!$url) {
                if ($this->isWordPressMU() && !Mage::helper('wpmultisite')->isDefaultBlog() && Mage::helper('wpmultisite')->getBlogId()) {
                    $url = $this->getBaseUrl('wp-content/uploads/sites/' . Mage::helper('wpmultisite')->getBlogId() . '/');
                } else {
                    $url = $this->getBaseUrl('wp-content/uploads/');
                }
            }
        }

        return rtrim($url, '/') . '/';
    }

    /**
     * Retrieve the local path to file cache path
     *
     * @return string
     */
    public function getFileCachePath()
    {
        return Mage::getBaseDir('var') . DS . 'wordpress' . DS;
    }

    /**
     * Retrieve the path for the WordPress installation
     * Return false if path is invalid
     *
     * @return false|string
     */
    public function getWordPressPath()
    {
        $path = $this->getRawWordPressPath();

        return is_dir($path) && is_file($path . 'wp-config.php')
        ? $path
        : false;
    }

    /**
     * Retrieve the path for the WordPress installation
     * Do not check, just return
     *
     * @return string
     */
    public function getRawWordPressPath()
    {
        $path = rtrim($this->getConfigValue('wordpress/integration/path'), DS);

        if ($path === '') {
            return false;
        }

        if (substr($path, 0, 1) !== DS) {
            return Mage::getBaseDir() . DS . $path . DS;
        }

        return rtrim($path, DS) . DS;
    }

    /**
     * Determine whether an addon is installed
     *
     * @param string $addon
     * @return bool
     */
    public function isAddonInstalled($addon)
    {
        if (strpos($addon, '_') === false) {
            $addon = 'Fishpig_Wordpress_Addon_' . $addon;
        }

        return (string) Mage::getConfig()->getNode('modules/' . $addon . '/active') === 'true';
    }

    /**
     * Provides backwards compatibility for older Magento versions running Legacy
     *
     * @param string $data
     * @param array $allowedTags = null
     * @return string
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        return Mage::helper('core')->htmlEscape($data, $allowedTags);
    }

    /**
     * Determine wether the Legacy add-on is installed
     *
     * @return bool
     */
    public function isLegacy()
    {
        if ($this->_isCached('is_legacy')) {
            return $this->_cached('is_legacy');
        }

        $isLegacy = is_file(Mage::getBaseDir() . DS . 'app' . DS . 'etc' . DS . 'modules' . DS . 'Fishpig_Wordpress_Addon_Legacy.xml');

        $this->_cache('is_legacy', $isLegacy);

        return $isLegacy;
    }

   /* //Added new code by MSS
    public function test($post)
    {
    	// echo "<pre>";print_r($post->getPostContent());die;
    	$content = $post->getPostContent();
        $pattern = $this->get_shortcode_regex();
        $op = preg_replace_callback( "/$pattern/s", array($this , 'do_shortcode_tag'), $content );
        echo "<pre>";print_r($op);
    }

    public function get_shortcode_regex()
    {
        $shortcode_tags = array
            (
            'embed'      => '__return_false',
            'wp_caption' => 'img_caption_shortcode',
            'caption'    => 'img_caption_shortcode',
            'gallery'    => 'gallery_shortcode',
            'playlist'   => 'wp_playlist_shortcode',
            'audio'      => 'wp_audio_shortcode',
            'video'      => 'wp_video_shortcode',
        );
        //echo "<pre>";print_r($shortcode_tags);die;
        $tagnames  = array_keys($shortcode_tags);
        $tagregexp = join('|', array_map('preg_quote', $tagnames));

        // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
        // Also, see shortcode_unautop() and shortcode.js.
        return
        '\\[' // Opening bracket
         . '(\\[?)' // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
         . "($tagregexp)"// 2: Shortcode name
         . '(?![\\w-])' // Not followed by word character or hyphen
         . '(' // 3: Unroll the loop: Inside the opening shortcode tag
         . '[^\\]\\/]*' // Not a closing bracket or forward slash
         . '(?:'
        . '\\/(?!\\])' // A forward slash not followed by a closing bracket
         . '[^\\]\\/]*' // Not a closing bracket or forward slash
         . ')*?'
        . ')'
        . '(?:'
        . '(\\/)' // 4: Self closing tag ...
         . '\\]' // ... and closing bracket
         . '|'
        . '\\]' // Closing bracket
         . '(?:'
        . '(' // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
         . '[^\\[]*+' // Not an opening bracket
         . '(?:'
        . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
         . '[^\\[]*+' // Not an opening bracket
         . ')*+'
        . ')'
        . '\\[\\/\\2\\]' // Closing shortcode tag
         . ')?'
            . ')'
            . '(\\]?)'; // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
    }

    public function do_shortcode_tag($m)
    {
        // echo "<pre>";print_r($m);die;
        $shortcode_tags = array
            (
            'embed'      => '__return_false',
            'wp_caption' => 'img_caption_shortcode',
            'caption'    => 'img_caption_shortcode',
            'gallery'    => 'gallery_shortcode',
            'playlist'   => 'wp_playlist_shortcode',
            'audio'      => 'wp_audio_shortcode',
            'video'      => 'wp_video_shortcode',
        );
        if ($m[1] == '[' && $m[6] == ']') {
            return substr($m[0], 1, -1);
        }

        $tag  = $m[2];
        $attr = $this->shortcode_parse_atts($m[3]);
        //echo "<pre>";print_r($attr);
        if (isset($m[5])) {
            // enclosing tag - extra parameter
            return $m[1] . call_user_func(array($this , $shortcode_tags[$tag]), array($attr, $m[5], $tag)) . $m[6];
        } else {
            // self-closing tag
            return $m[1] . call_user_func(array($this , $shortcode_tags[$tag]), array($attr, null, $tag)) . $m[6];
        }

    }

    public function shortcode_parse_atts($text)
    {
        $atts    = array();
        $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text    = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
            foreach ($match as $m) {
                if (!empty($m[1])) {
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                } elseif (!empty($m[3])) {
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                } elseif (!empty($m[5])) {
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                } elseif (isset($m[7]) && strlen($m[7])) {
                    $atts[] = stripcslashes($m[7]);
                } elseif (isset($m[8])) {
                    $atts[] = stripcslashes($m[8]);
                }

            }
        } else {
            $atts = ltrim($text);
        }
        return $atts;
    }

    public function wp_video_shortcode($a, $b = null, $c = null)
    {
        // echo "<pre>";print_r($a);die;
        $html = '<video height="352" width="625" controls="controls" preload="metadata" id="video-35542-1" class="wp-video-shortcode"><source src=' . $a[0]['mp4'] . ' type="video/mp4"></source></video>';

        return $html;
    }
    

    public function wp_playlist_shortcode($a, $b = null, $c = null)
    {
        // echo "<pre>";print_r($a);die;
        $html = '';

        return $html;
    }*/
}
