<?php

namespace Addons\Censor;

use Addons\Censor\Censor;

class Factory {

    public $loader;

    public function __construct(CensorLoader $loader)
    {
        $this->loader = $loader;
    }

    public function make(string $key, array $attributes, array $replacement = null): Censor
    {
        return new Censor($this->loader, $key, $attributes, $replacement);
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace(string $namespace, string $hint = null): void
    {
        $this->loader->addNamespace($namespace, $hint);
    }

}
