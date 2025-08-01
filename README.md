<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

---

## Tentang Proyek Ini

Proyek ini dibangun menggunakan **Laravel**, sebuah _framework_ aplikasi web yang dikenal karena sintaksnya yang elegan dan ekspresif. Laravel dirancang untuk membuat proses pengembangan menjadi lebih menyenangkan dan kreatif.

Beberapa fitur utama yang mempermudah pengembangan dengan Laravel meliputi:

* **Sistem _Routing_** yang cepat dan sederhana.
* **_Dependency Injection Container_** yang sangat andal.
* Pilihan _backend_ untuk **_session_** dan **_cache_**.
* **_Database ORM_** (Eloquent) yang intuitif untuk berinteraksi dengan basis data.
* **_Schema Migrations_** yang memudahkan pengelolaan skema basis data.
* **Pemrosesan _background job_** yang tangguh.
* **_Event Broadcasting_** untuk fungsionalitas _real-time_.

---

## Instruksi Instalasi

Untuk menjalankan proyek ini di lingkungan lokal Anda, ikuti langkah-langkah berikut:

1.  **Kloning repositori ini:**
    ```bash
    git clone [URL_REPOSITORI_ANDA]
    cd [NAMA_FOLDER_PROYEK_ANDA]
    ```

2.  **Instal dependensi Composer:**
    ```bash
    composer install
    ```

3.  **Salin file `.env.example` dan konfigurasikan:**
    ```bash
    cp .env.example .env
    ```
    Buka file `.env` dan atur detail koneksi basis data Anda.

4.  **Buat _Application Key_:**
    ```bash
    php artisan key:generate
    ```

5.  **Jalankan migrasi basis data:**
    ```bash
    php artisan migrate
    ```

6.  **Jalankan server pengembangan:**
    ```bash
    php artisan serve
    ```
    Aplikasi akan berjalan di `http://127.0.0.1:8000`.

---

## Perintah-perintah Berguna

Berikut adalah beberapa perintah `artisan` khusus yang mungkin Anda perlukan untuk proyek ini:

* **Membuat modul baru:**
    ```bash
    php artisan make:module {name : Nama modul (contoh: User)}
    ```

* **Membuat _resource_ modul:**
    ```bash
    php artisan make:module-resources {name : Nama resource (contoh: User)} --module={module_name : Nama modul (contoh: Admin)} --table={table_name : Nama tabel (contoh: users)}
    ```

---

## Dokumentasi dan Pembelajaran

Jika Anda ingin mempelajari Laravel lebih dalam, ada banyak sumber daya yang bisa digunakan:

* **Dokumentasi Resmi:** [laravel.com/docs](https://laravel.com/docs)
* **Tutorial Video:** [Laracasts](https://laracasts.com) memiliki ribuan video tutorial tentang Laravel, PHP, _unit testing_, dan JavaScript.
* **_Bootcamp_ Laravel:** [bootcamp.laravel.com](https://bootcamp.laravel.com) adalah panduan interaktif untuk membangun aplikasi Laravel dari awal.

---

## Kontribusi

Kami menyambut kontribusi dari siapa pun! Silakan baca panduan kontribusi di [dokumentasi Laravel](https://laravel.com/docs/contributions) untuk informasi lebih lanjut. Pastikan juga untuk mengikuti [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct) yang berlaku.

---

## Sponsor

Terima kasih kepada para sponsor berikut yang telah mendukung pengembangan Laravel. Jika Anda tertarik untuk menjadi sponsor, kunjungi [Laravel Partners program](https://partners.laravel.com).

* **Vehikl**
* **Tighten Co.**
* **Kirschbaum Development Group**
* **64 Robots**
* **Curotec**
* **DevSquad**
* **Redberry**
* **Active Logic**

---

## Lisensi

_Framework_ Laravel adalah _software_ _open-source_ yang dilisensikan di bawah [Lisensi MIT](https://opensource.org/licenses/MIT).
