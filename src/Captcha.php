<?php

namespace Esoftdream\Captcha;

use Config\Services;
use Exception;

class Captcha
{
    protected static $fonts = [];
    protected static $backgrounds = [];
    protected static $characters;
    protected static $case_sensitive = false;

    public static function make($case_sensitive = false)
    {
        helper('text');
        $encrypter = Services::encrypter();

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
            random_string('alnum', 5)
        );

        $characters = static::$case_sensitive ? static::$characters : strtolower(static::$characters);
        $hash = $encrypter->encrypt($characters);

        // save to session
        session()->set('captcha.hash', $hash);

        $bg = static::background();
        $font = static::font();
        $info = getimagesize($bg);
        $old = null;

        switch ($info['mime']) {
            case 'image/jpg':
            case 'image/jpeg': $old = imagecreatefromjpeg($bg);
                break;

            case 'image/gif':  $old = imagecreatefromgif($bg);
                break;

            case 'image/png':  $old = imagecreatefrompng($bg);
                break;

            default:
                throw new Exception('Only JPG, PNG and GIF are supported for backgrounds.');
        }

        // default settings
        $width = 296;
        $height = 53;
        $space = 20;

        $new = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($new, 255, 255, 255);

        imagefilledrectangle($new, 0, 0, $width - 1, $height - 1, $bg);
        imagecopyresampled($new, $old, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
        imagedestroy($old);

        $color = md5(random_string('alnum', 5));

        for ($i = 0; $i < 5; $i++) {
            $colors = [
                hexdec(substr($color, mt_rand(0, 31), 2)),
                hexdec(substr($color, mt_rand(0, 31), 2)),
                hexdec(substr($color, mt_rand(0, 31), 2)),
                hexdec(substr($color, mt_rand(0, 31), 2)),
                hexdec(substr($color, mt_rand(0, 31), 2)),
            ];

            $gap = 10 + ($i * $space);
            $w = mt_rand(-10, 15);
            $h = mt_rand($height - 10, $height - 5);
            $fg = imagecolorallocate($new, $colors[mt_rand(1, 3)], $colors[mt_rand(1, 4)], $colors[mt_rand(0, 4)]);

            imagettftext($new, mt_rand(18, 20), $w, $gap, $h, $fg, $font, static::$characters[$i]);
        }

        // Start output buffering
        ob_start();

        // Output the image as PNG into the buffer
        imagepng($new);

        // Get the buffered image data
        $imageData = ob_get_clean();

        // Encode the image data as base64
        $base64Image = base64_encode($imageData);

        // Return the base64 encoded image data
        return $base64Image;
    }

    public static function show()
    {
        return '<img src="data:image/png;base64,' . static::make() . '" alt="">';
    }

    public static function check($value)
    {
        $value = trim((string) (static::$case_sensitive ? $value : strtolower($value)));
        $hash = session()->get('captcha.hash');

        return $value && $hash && static::hash_check($value, $hash);
    }

    public static function hash_check($value, $hash): bool
    {
        $encrypter = Services::encrypter();

        $hash_ori = $encrypter->decrypt($hash);

        return (bool) ($hash_ori === $value);
    }

    public static function url()
    {
        return base_url('captcha') . '?' . mt_rand(1, 100000);
    }

    protected static function fonts()
    {
        $directory = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR;
        $fonts = glob($directory . '*.ttf');
        static::$fonts = (is_array($fonts) && ! empty($fonts)) ? $fonts : [];
    }

    protected static function backgrounds()
    {
        $directory = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'backgrounds' . DIRECTORY_SEPARATOR;
        $backgrounds = glob($directory . '*'); // Retrieve all files and directories in the specified directory

        static::$backgrounds = (is_array($backgrounds) && ! empty($backgrounds)) ? $backgrounds : [];
    }

    protected static function background()
    {
        if (empty(static::$backgrounds)) {
            throw new Exception('No backgrounds found to operate with.');
        }

        return static::$backgrounds[mt_rand(0, count(static::$backgrounds) - 1)];
    }

    protected static function font()
    {
        if (empty(static::$fonts)) {
            throw new Exception('No fonts found to operate with.');
        }

        return static::$fonts[mt_rand(0, count(static::$fonts) - 1)];
    }
}
