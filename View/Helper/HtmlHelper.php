<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Http\View\Helper;

use Origin\Core\Plugin;
use Origin\Http\Router;
use Origin\Inflector\Inflector;
use Origin\Http\View\TemplateTrait;
use Origin\Http\View\Exception\NotFoundException;

class HtmlHelper extends Helper
{
    use TemplateTrait;
    
    protected $defaultConfig = [
        'templates' => [
            'a' => '<a href="{url}"{attributes}>{text}</a>',
            'css' => '<link rel="stylesheet" type="text/css" href="{url}" />',
            'js' => '<script type="text/javascript" src="{url}"></script>',
            'img' => '<img src="{src}"{attributes}>',
            'tag' => '<{tag}{attributes}>{content}</{tag}>',
        ],
    ];

    /**
     * Wraps content in a div
     *
     * @param string $content
     * @param array $attributes
     * @return string
     */
    public function div(string $content, array $attributes = []): string
    {
        $options = [
            'tag' => 'div',
            'content' => $content,
            'attributes' => $this->attributesToString($attributes),
        ];

        return $this->templater()->format('tag', $options);
    }
    /**
     * Generates a link
     *
     * @param string $text
     * @param array|string $url
     * @param array $attributes
     * @return string
     */
    public function link(string $text, $url, array $attributes = []) : string
    {
        $options = [
            'text' => $text,
            'url' => Router::url($url),
            'attributes' => $this->attributesToString($attributes),
            'escape' => true,
        ];

        return $this->templater()->format('a', $options);
    }

    /**
     * Generates Stylesheet link or styles block for plugin css
     * $html->css('form'); // /css/form.css
     * $html->css('/assets/css/form.css');
     * html->css('Myplugin.form.css'); // remember to include extension
     * @param string $path
     * @return string
     */
    public function css(string $path) : string
    {
        return $this->asset($path, ['ext' => 'css']);
    }

    /**
     * Image tag, must provide extension, if it does not start with / it will
     * assume /img/ folder
     *
     * @param string $image
     * @param array $attributes
     * @return string
     */
    public function img(string $image, array $attributes = []) : string
    {
        if ($image[0] !== '/') {
            $image .= '/img/' . $image;
        }
        $options = [
            'src' => $image,
            'attributes' => $this->attributesToString($attributes),
        ];

        return $this->templater()->format('img', $options);
    }

    /**
        * Generates script link or block for plugin js
        * $html->js('form'); // /js/form.js
        * $html->js('/assets/js/form.js');
        * html->js('Myplugin.form.js'); // remember to include extension
        * @param string $path
        * @return string
        */
    public function js(string $path) : string
    {
        return $this->asset($path, ['ext' => 'js']);
    }

    private function asset($path, $options) : string
    {
        // without path $html->css('https://example.com/something.css');
        if (strpos($path, '://') !== false) {
            return $this->templater()->format($options['ext'], ['url' => $path]);
        }
        $plugin = null;
        list($a, $b) = pluginSplit($path);
        if (Plugin::loaded($a) === true) {
            $plugin = $a;
            $path = $b;
        }
        $length = strlen('.' . $options['ext']);
        if (substr($path, -$length) !== '.' . $options['ext']) {
            $path .= '.' . $options['ext'];
        }
        // without path $html->css('form');
        if (! $plugin && $path[0] !== '/') {
            $path = DS .$options['ext'] . DS . $path;
        }

        if ($plugin) {
            $filename = PLUGINS . DS . Inflector::underscored($plugin) . DS . 'public' . DS . $options['ext'] . DS . $path;
            if ($options['ext'] === 'js') {
                return '<script>' .$this->loadFile($filename) . '</script>';
            }
   
            return '<style>' .$this->loadFile($filename) . '</style>';
        }

        return $this->templater()->format($options['ext'], ['url' => $path]);
    }
 
    protected function loadFile(string $filename) : string
    {
        if (! file_exists($filename)) {
            throw new NotFoundException($filename . ' not found.');
        }

        return file_get_contents($filename);
    }
}
