<?php

namespace Addons\Censor\File;

use Illuminate\Translation\FileLoader as Base;

class FileLoader extends Base {

    /**
     * 此函数得到的是第一个搜索目录下的文件路径，绝大部分情况下够用
     * 不过FileLoader支持多个搜索目录
     */
    public function getPath(string $locale, string $group, string $namespace = null): string {
        if (is_null($namespace) || $namespace == '*')
            return $this->getBasePath($this->paths[0], $locale, $group);

        return $this->getNamespacedPath($locale, $group, $namespace);
    }

    public function getBasePath(string $path, string $locale, string $group): string {
        return "{$path}/{$locale}/{$group}.php";
    }

    public function getNamespacedPath(string $locale, string $group, string $namespace = null): string {
        if (isset($this->hints[$namespace])) {
            return $this->getBasePath($this->hints[$namespace], $locale, $group);
        } else {
            return "{$this->paths[0]}/vendor/{$namespace}/{$locale}/{$group}.php";
        }
    }

}
