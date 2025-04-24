<?php

namespace atc\WHx4\Core;

class PostUtils
{
    public static function say_hello()
    {
        echo "<!-- Hello from Rex! -->";
    }

    public static function merge_titles($title1, $title2)
    {
        return $title1 . ' / ' . $title2;
    }
}

