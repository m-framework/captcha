<?php

namespace modules\captcha\client;

use m\module;
use m\config;
use m\registry;
use modules\captcha\models\captcha_words;

class captcha extends module {

    public static $_name = '*Captcha code*';

    static public function get_captcha()
    {
        $gd_info = gd_info();

        if (empty($gd_info['FreeType Support']) || empty($gd_info['FreeType Support'])) {
            return false;
        }

        $captcha_unique_id = self::captcha_encode(microtime());

        $word = trim((string)captcha_words::get_random());

        if (strlen($word) < 6) {

            $append_arr = [1, 2];
            $append_number = $append_arr[array_rand($append_arr)];
            $number = rand(0, 9);

            switch ($append_number) {
                case 1:
                    $word .= $number;
                    break;
                case 2:
                    $word = $number . $word;
                    break;
            }
        }

        $font_path = config::get('root_path') . static::get_path() . '/fonts/Roboto-Regular.ttf';

        $bg_path = config::get('root_path') . static::get_path() . '/img/captcha_bg.png';

        $im = imagecreatefrompng($bg_path);

        $left = 5;

        for($i=0; $i < mb_strlen($word, "UTF-8"); $i++) {

            $letter = mb_substr($word, $i, 1, "UTF-8");

            if (is_numeric($letter) && (int)$letter == 1) {
                $angle = 0;
            }
            else {
                $rotate_arr = [1, -1];
                $angle = rand(0, 15) * $rotate_arr[array_rand($rotate_arr)];
            }

            $color_part = rand(0, 225);
            $color = imagecolorallocate($im, $color_part, $color_part, $color_part);

            if (in_array($letter, ['j','y','g','p','q'])) {
                $y = 28;
            }
            else {
                $y = 34;
            }

            imagettftext ($im, 36, $angle, $left, $y, $color, $font_path, $letter);

            if (in_array($letter, ['i','l','j'])) {
                $left += 7;
            }
            else if (in_array($letter, ['t','r','f'])) {
                $left += 12;
            }
            else if (in_array($letter, ['m','w'])) {
                $left += 23;
            }
            else {
                $left += rand(16, 18);
            }
        }

        ob_start();
        ImagePNG($im);
        $captcha_image = base64_encode(ob_get_contents());
        ob_end_clean();
        ImageDestroy ($im);

        if (!empty($captcha_image)) {
            $_SESSION['captcha_' . $captcha_unique_id] = self::captcha_encode($word);
        }

        return (object)['captcha_unique_id' => $captcha_unique_id, 'captcha_image' => $captcha_image];
    }

    static public function verify()
    {
        if (!registry::has('post')) {
            return false;
        }

        $post = registry::get('post');

        if (empty($post->captcha_unique_id) || empty($post->captcha_code)
            || empty($_SESSION['captcha_' . $post->captcha_unique_id])
        ) {
            return false;
        }

        if ($_SESSION['captcha_' . $post->captcha_unique_id] == self::captcha_encode($post->captcha_code)
            && self::clean($post->captcha_unique_id)) {
            return $post->captcha_code;
        }

        return false;
    }

    static public function clean($code)
    {
        unset($_SESSION['captcha_' . $code]);
        return true;
    }

    static public function captcha_encode($str)
    {
        return strrev(md5(md5(strrev(md5(strrev($str) . config::get('host'))))));
    }
}