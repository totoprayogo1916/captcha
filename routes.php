<?php

defined('DS') or exit('No direct script access.');

Route::get('captcha', function () {
    return Esyede\Captcha::make();
});
