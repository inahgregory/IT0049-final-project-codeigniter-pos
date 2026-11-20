<?php
namespace Config;
use App\Filters\StaffAuth;
use CodeIgniter\Config\Filters as BaseFilters;
class Filters extends BaseFilters
{
    public array $aliases = [
        'csrf' => \CodeIgniter\Filters\CSRF::class,
        'invalidchars' => \CodeIgniter\Filters\InvalidChars::class,
        'secureheaders' => \CodeIgniter\Filters\SecureHeaders::class,
        'forcehttps' => \CodeIgniter\Filters\ForceHTTPS::class,
        'auth' => StaffAuth::class,
    ];
    public array $required = ['before' => ['forcehttps'], 'after' => []];
    public array $globals = ['before' => ['csrf', 'invalidchars'], 'after' => ['secureheaders']];
    public array $methods = [];
    public array $filters = [];
}
