<?php

use Crumbls\FilamentMediaLibrary\Traits\HasMediaLibrary;
use Illuminate\Database\Eloquent\Model;

if (! class_exists('FmlTestModel')) {
    class FmlTestModel extends Model
    {
        use HasMediaLibrary;

        protected $table = 'users';

        protected $fillable = ['name', 'email', 'password'];
    }
}

function createTestGifFile(): string
{
    $path = tempnam(sys_get_temp_dir(), 'fml_test_');
    file_put_contents($path, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
    rename($path, $path.'.gif');

    return $path.'.gif';
}

function createTestTextFile(string $content = 'Hello world'): string
{
    $path = tempnam(sys_get_temp_dir(), 'fml_test_');
    file_put_contents($path, $content);
    rename($path, $path.'.txt');

    return $path.'.txt';
}

function createTestPdfFile(): string
{
    $path = tempnam(sys_get_temp_dir(), 'fml_test_');
    file_put_contents($path, '%PDF-1.4 test content');
    rename($path, $path.'.pdf');

    return $path.'.pdf';
}
