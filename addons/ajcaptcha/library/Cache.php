<?php

namespace addons\ajcaptcha\library;

class Cache extends \think\Cache
{
    public static function delete($name)
    {
        return self::rm($name);
    }
}