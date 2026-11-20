<?php
namespace App\Libraries;
use CodeIgniter\HTTP\Files\UploadedFile;
class ImageStore
{
    public function prepare(?UploadedFile $upload, string $kind): ?string
    {
        if (! $upload || $upload->getError() === UPLOAD_ERR_NO_FILE) { return null; }
        if (! $upload->isValid()) { throw new \DomainException('The picture could not be uploaded. Try again.'); }
        if ($upload->getSize() > 2 * 1024 * 1024) { throw new \DomainException('The picture must be no larger than 2 MB.'); }
        return $this->preparePath($upload->getTempName(), $kind);
    }
    // Called with a server-controlled temporary path, never with a request path.
    public function preparePath(string $source, string $kind): string
    {
        if (! in_array($kind, ['products', 'avatars'], true)) { throw new \DomainException('Invalid picture category.'); }
        $size = @getimagesize($source);
        if (! $size || ! in_array($size['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new \DomainException('Choose a real JPEG, PNG, or WebP image.');
        }
        if ($size[0] > 4096 || $size[1] > 4096) { throw new \DomainException('The picture must be at most 4096 by 4096 pixels.'); }
        $path = $kind.'/'.bin2hex(random_bytes(16)).'.jpg';
        $target = WRITEPATH.'uploads/'.$path;
        if (! is_dir(dirname($target))) { mkdir(dirname($target), 0775, true); }
        try {
            $image = \Config\Services::image('gd', null, false)->withFile($source);
            $saved = $image->fit($kind === 'avatars' ? 256 : 640, $kind === 'avatars' ? 256 : 480)
                ->convert(IMAGETYPE_JPEG)->save($target, 85);
            if (! $saved) { throw new \RuntimeException('The prepared image could not be saved.'); }
        } catch (\Throwable $exception) {
            if (is_file($target)) { unlink($target); }
            log_message('error', 'Image preparation failed: {message}', ['message' => $exception->getMessage()]);
            throw new \DomainException('This picture could not be prepared. Please choose another image.');
        }
        return $path;
    }
    public function delete(?string $path): void
    {
        if ($path && preg_match('~^(products|avatars)/[a-f0-9]{32}\.jpg$~D', $path)) {
            $file = WRITEPATH.'uploads/'.$path;
            if (is_file($file)) { unlink($file); }
        }
    }
}

