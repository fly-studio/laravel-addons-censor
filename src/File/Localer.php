<?php

namespace Addons\Censor\File;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\NamespacedItemResolver;

abstract class Localer extends NamespacedItemResolver {

    /**
     * The loader implementation.
     *
     * @var FileLoader
     */
    protected FileLoader $loader;

    /**
     * The default locale being used by the LocalePool.
     *
     * @var string
     */
    protected string $locale;

    /**
     * The fallback locale used by the LocalePool.
     *
     * @var string
     */
    protected string $fallback;

    /**
     * The array of loaded locale groups.
     *
     * @var array
     */
    protected array $loaded = [];

    /**
     * Create a new LocalePool instance.
     *
     * @param  FileLoader  $loader
     * @param  string  $locale
     * @return void
     */
    public function __construct(FileLoader $loader, string $locale)
    {
        $this->loader = $loader;
        $this->locale = $locale;
    }

    /**
     * Load the specified language group.
     * 使用FileLoader读取文件
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return void
     */
    public function load(string $namespace, string $group, string $locale = null)
    {
        if ($this->isLoaded($namespace, $group, $locale)) {
            return;
        }

        // The loader is responsible for returning the array of language lines for the
        // given namespace, group, and locale. We'll set the lines in this array of
        // lines that have already been loaded so that we can easily access them.
        $lines = $this->loader->load($locale, $group, $namespace);

        $this->loaded[$namespace][$group][$locale] = $lines;
    }

    /**
     * Add locale lines to the given locale.
     *
     * @param  array  $lines
     * @param  string  $locale
     * @param  string  $namespace
     * @return void
     */
    public function addLines(array $lines, string $locale = null, string $namespace = '*')
    {
        foreach ($lines as $key => $value) {
            list($group, $item) = explode('.', $key, 2);

            Arr::set($this->loaded, "$namespace.$group.$locale.$item", $value);
        }
    }

    /**
     * Determine if a translation exists for a given locale.
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @return bool
     */
    public function hasForLocale(string $key, string $locale = null)
    {
        return $this->has($key, $locale, false);
    }

    /**
     * Determine if a translation exists.
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return bool
     */
    public function has(string $key, string $locale = null, bool $fallback = true)
    {
        return !is_null($this->get($key, [], $locale, $fallback));
    }

    /**
     * Get the translation for the given key.
     * 此函数参照的读取翻译文件的函数
     * 当$key为namespace::filename.key时，获取具体的key的value项
     * 当$key为namespace::filename时，获取的是整个文件
     *
     * 一般情况下Line指namespace::filename.key
     *
     * @param  string  $key
     * @param  array|null   $replacement
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array|null
     */
    public function getLine(string $key, string $locale = null, bool $fallback = true)
    {
        list($namespace, $group, $item) = $this->parseKey($key);

        // Here we will get the locale that should be used for the language line. If one
        // was not passed, we will use the default locales which was given to us when
        // the translator was instantiated. Then, we can load the lines and return.
        $locales = $fallback ? $this->localeArray($locale)
                             : [$locale ?: $this->locale];

        foreach ($locales as $locale) {
            if (! is_null($line = $this->read(
                $namespace, $group, $locale, $item
            ))) {
                break;
            }
        }

        return isset($line) ? $line : null;
    }

    /**
     * Get the array of locales to be checked.
     *
     * @param  string|null  $locale
     * @return array
     */
    protected function localeArray(string $locale = null)
    {
        return array_filter([$locale ?: $this->locale, $this->fallback]);
    }

    /**
     * Determine if the given group has been loaded.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return bool
     */
    protected function isLoaded(string $namespace, string $group, string $locale)
    {
        return isset($this->loaded[$namespace][$group][$locale]);
    }

    /**
     * Retrieve a data out the loaded array.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @param  string  $item
     * @return string|array|null
     */
    protected function read(string $namespace, string $group, string $locale, $item = null)
    {
        $this->load($namespace, $group, $locale);

        $data = Arr::get($this->loaded[$namespace][$group][$locale], $item);

        return $data;
    }

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function locale()
    {
        return $this->getLocale();
    }

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the default locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get the fallback locale being used.
     *
     * @return string
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * Set the fallback locale being used.
     *
     * @param  string  $fallback
     * @return void
     */
    public function setFallback(string $fallback = null)
    {
        $this->fallback = $fallback;
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace(string $namespace, $hint = null)
    {
        $this->loader->addNamespace($namespace, $hint);
    }

    /**
     * Get the language line loader implementation.
     *
     * @return \Illuminate\Contracts\Translation\Loader
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Parse a key into namespace, group, and item.
     *
     * @param  string  $key
     * @return array
     */
    public function parseKey($key)
    {
        $segments = parent::parseKey($key);

        if (is_null($segments[0])) {
            $segments[0] = '*';
        }

        return $segments;
    }

    /**
     * 获取文件路径
     * 注意：由于FileLoader支持多个搜索目录，此函数得到的结果是第一个目录的文件路径，大部分情况下够用。目前也只有异常时在使用。
     */
    public function getPath(string $key, bool $fallback = false)
    {
        [$namespace, $group, $item] = $this->parseKey($key);
        return $this->loader->getPath($fallback ? $this->fallback : $this->locale, $group, $namespace);
    }

    public abstract function get(string $key, array $ruleKeys, string $locale = null, bool $fallback = true);

}
