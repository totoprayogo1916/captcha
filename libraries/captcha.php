<?php

namespace Esyede;

defined('DS') or exit('No direct script access.');

use System\URL;
use System\Str;
use System\Hash;
use System\Session;

class Captcha
{
    protected static $fonts = [];
    protected static $backgrounds = [];
    protected static $characters;
    protected static $case_sensitive = false;

    public static function make($case_sensitive = false)
    {
        if (empty(static::$backgrounds)) {
            static::backgrounds();
        }

        if (empty(static::$fonts)) {
            static::fonts();
        }

        static::$case_sensitive = (bool) $case_sensitive;
        static::$characters = str_replace(
            ['0', '1', '5', 'i', 'I', 'k', 'K', 'l', 'L', 'o', 'O', 's', 'S', 'w', 'W'],
            ['6', '4', '8', '2', '3', 'z', 'Z', 'p', 'P', 'h', 'H', 'x', 'X', 'v', 'V'],
            Str::random(5)
        );

        $characters = static::$case_sensitive ? static::$characters : strtolower(static::$characters);
        Session::put('captcha.hash', Hash::make($characters));

        $bg = static::background();
        $font = static::font();
        $info = getimagesize($bg);
        $old = null;

        switch ($info['mime']) {
            case 'image/jpg':
            case 'image/jpeg': $old = imagecreatefromjpeg($bg); break;
            case 'image/gif':  $old = imagecreatefromgif($bg);  break;
            case 'image/png':  $old = imagecreatefrompng($bg);  break;
            default:           throw new \Exception('Only JPG, PNG and GIF are supported for backgrounds.');
        }

        // default settings
        $width = 120;
        $height = 30;
        $space = 20;

        $new = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($new, 255, 255, 255);

        imagefilledrectangle($new, 0, 0, $width - 1, $height - 1, $bg);
        imagecopyresampled($new, $old, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
        imagedestroy($old);

        $color = md5(Str::random(5));

        for ($i = 0; $i < 5; $i++) {
            $colors = [
                hexdec(substr($color, rand(0, 31), 2)),
                hexdec(substr($color, rand(0, 31), 2)),
                hexdec(substr($color, rand(0, 31), 2)),
                hexdec(substr($color, rand(0, 31), 2)),
                hexdec(substr($color, rand(0, 31), 2)),
            ];

            $gap = 10 + ($i * $space);
            $w = rand(-10, 15);
            $h = rand($height - 10, $height - 5);
            $fg = imagecolorallocate($new, $colors[rand(1, 3)], $colors[rand(1, 4)], $colors[rand(0, 4)]);

            imagettftext($new, rand(18, 20), $w, $gap, $h, $fg, $font, static::$characters[$i]);
        }

        header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        header('Pragma: no-cache');
        header('Content-type: image/png');
        header('Content-Disposition: inline; filename=captcha.png');

        return imagepng($new);
    }

    public static function check($value)
    {
        $value = trim((string) (static::$case_sensitive ? $value : strtolower($value)));
        $hash = Session::get('captcha.hash', '');
        return $value && $hash && Hash::check($value, $hash);
    }

    public static function url()
    {
        return URL::to('captcha?'.mt_rand(1, 100000));
    }

    protected static function fonts()
    {
        $fonts = glob(path('assets').'packages'.DS.'captcha'.DS.'fonts'.DS.'*.ttf');
        static::$fonts = (is_array($fonts) && ! empty($fonts)) ? $fonts : [];
    }

    protected static function backgrounds()
    {
        $backgrounds = glob(path('assets').'packages'.DS.'captcha'.DS.'backgrounds'.DS.'*.png');
        static::$backgrounds = (is_array($backgrounds) && ! empty($backgrounds)) ? $backgrounds : [];
    }

    protected static function background()
    {
        if (empty(static::$backgrounds)) {
            throw new \Exception('No backgrounds found to operate with.');
        }

        return static::$backgrounds[rand(0, count(static::$backgrounds) - 1)];
    }

    protected static function font()
    {
        if (empty(static::$fonts)) {
            throw new \Exception('No fonts found to operate with.');
        }

        return static::$fonts[rand(0, count(static::$fonts) - 1)];
    }
}
