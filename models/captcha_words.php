<?php

namespace modules\captcha\models;

use m\model;

class captcha_words extends model
{
    private static $words = [];
	
	static public function get_random()
	{
        if (empty(static::$words) && is_file(__DIR__ . '/words.php')) {
            static::$words = include(__DIR__ . '/words.php');
        }

        shuffle(static::$words);

        return static::$words[array_rand((array)static::$words)];
	}
}
