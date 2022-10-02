<?php

defined('DS') or exit('No direct script access.');

Autoloader::map(['Esyede\Captcha' => __DIR__.DS.'libraries'.DS.'captcha.php']);

System\Validator::register('captcha', function ($attribute, $value) {
    return Esyede\Captcha::check($value);
});
