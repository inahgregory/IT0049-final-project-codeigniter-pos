<?php
namespace App\Controllers;
use CodeIgniter\Controller;
abstract class BaseController extends Controller
{
    protected $helpers = ['url', 'form', 'pos'];
    protected function invalid(string $url, array $errors, array $data = [])
    {
        unset($data['password'], $data['password_confirmation']);
        session()->setFlashdata('errors', $errors);
        session()->setFlashdata('form_old', $data);
        return redirect()->to(site_url($url));
    }
    protected function input(array $fields): array
    {
        $data = [];
        foreach ($fields as $field) {
            $value = $this->request->getPost($field);
            $data[$field] = is_string($value) && ! str_contains($field, 'password') ? trim($value) : $value;
        }
        return $data;
    }
    protected function record(\CodeIgniter\Model $model, int $id): array
    {
        $row = $model->find($id);
        if (! $row) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        return $row;
    }
}

