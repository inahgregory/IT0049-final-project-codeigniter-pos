<?php
function old_field(string $key, $default = ''): string
{
    $old = session()->getFlashdata('form_old') ?? [];
    $value = array_key_exists($key, $old) ? $old[$key] : $default;
    return is_scalar($value) ? (string) $value : '';
}
function peso($value): string { return number_format((float) $value, 2); }
function record_image(?string $path, string $kind = 'products'): string
{
    return $path ? site_url('media/'.$path) : base_url('images/'.($kind === 'avatars' ? 'avatar-placeholder.svg' : 'product-placeholder.svg'));
}
