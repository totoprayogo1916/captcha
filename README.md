# captcha

<p align="center"><img src="screenshot.png" alt="captcha"></p>

Paket captcha sederhana untuk rakit framework.

## Instalasi
Jalankan perintah ini via rakit console:

```sh
php rakit package:install captcha
```


## Mendaftarkan paket

Tambahkan kode berikut ke file `application/packages.php`:

```php
'captcha' => ['autoboot' => true],
```

Tambahkan pesan kustom validasi berikut ke `application\language\en\validation.php`:

```php
'custom' => [
    'captcha_captcha' => 'Captcha mismatch.',
],
```

Tambahkan juga ke `application\language\id\validation.php`:

```php
'custom' => [
    'captcha_captcha' => 'Captcha tidak cocok.',
],
```


## Cara penggunaan

**1. Menampilkan gambar captcha di view:**

```blade
<img src="{{ Esyede\Captcha::url() }}" id="captcha" alt="captcha"/>
<br>
<input type="text" name="captcha">
```


**2. Validasi captcha di controller:**

```php
$validation = Validator::make(Input::all(), [
    // ...
    'captcha' => 'required|captcha',
    // ...
]);

if ($validation->fails()) {
    return Redirect::back()
        ->with_input()
        ->with_errors($validation);
}
```


## Lisensi

Paket ini dirilis dibawah [Lisensi MIT](https://github.com/esyede/captcha/blob/master/LICENSE)
