<?php
namespace App\Controllers;
class Media extends BaseController
{
    public function show(string $kind, string $name)
    {
        if (! in_array($kind, ['products', 'avatars'], true) || ! preg_match('/^[a-f0-9]{32}\.jpg$/D', $name)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $path = WRITEPATH.'uploads/'.$kind.'/'.$name;
        if (! is_file($path)) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        return $this->response->setContentType('image/jpeg')->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, max-age=3600')->setBody(file_get_contents($path));
    }
}
