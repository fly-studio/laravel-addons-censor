<?php

if (! function_exists('censor')) {
    function censor($key, $attributes, $replacement = [])
    {
        return app('censor')->make($key, $attributes, $replacement);
    }
}

if (! function_exists('validator')) {
    function validator($data, $key, $attributes, $replacement = [])
    {
        return censor($key, $attributes, $replacement)->data($data)->validator();
    }
}

