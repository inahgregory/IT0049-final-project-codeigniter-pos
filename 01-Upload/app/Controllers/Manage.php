<?php
namespace App\Controllers;
use App\Libraries\ImageStore;
abstract class Manage extends BaseController
{
    protected string $resource;
    protected function spec(): array
    {
        return match ($this->resource) {
            'products' => ['title' => 'Products', 'singular' => 'Product', 'model' => \App\Models\ProductModel::class,
                'fields' => ['name' => ['Product name', 'text', true], 'price' => ['Price (PHP)', 'number', true], 'stock_quantity' => ['Stock quantity', 'number', true]], 'image' => 'image', 'kind' => 'products'],
            'customers' => ['title' => 'Customers', 'singular' => 'Customer', 'model' => \App\Models\CustomerModel::class,
                'fields' => ['full_name' => ['Full name', 'text', true], 'email' => ['Email', 'email', true], 'phone' => ['Phone', 'tel', false]], 'image' => null, 'kind' => ''],
            'staff' => ['title' => 'Staff', 'singular' => 'Staff member', 'model' => \App\Models\StaffModel::class,
                'fields' => ['username' => ['Username', 'text', true], 'full_name' => ['Full name', 'text', true], 'password' => ['Password', 'password', true]], 'image' => 'avatar', 'kind' => 'avatars'],
        };
    }
    protected function model(): \CodeIgniter\Model { $class = $this->spec()['model']; return new $class(); }
    public function index()
    {
        $spec = $this->spec();
        $archived = $this->request->getGet('archived') === '1';
        $rows = $this->model()->where('is_archived', $archived ? 1 : 0)->orderBy('id', 'DESC')->findAll();
        return view('manage/index', compact('spec', 'rows', 'archived') + ['title' => $spec['title'], 'resource' => $this->resource]);
    }
    public function create()
    {
        return view('manage/form', ['title' => 'Add '.$this->spec()['singular'], 'spec' => $this->spec(), 'row' => null, 'resource' => $this->resource]);
    }
    public function edit(int $id)
    {
        $row = $this->record($this->model(), $id);
        if ($row['is_archived']) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        return view('manage/form', ['title' => 'Edit '.$this->spec()['singular'], 'spec' => $this->spec(), 'row' => $row, 'resource' => $this->resource]);
    }
    public function store() { return $this->persist(null); }
    public function update(int $id) { return $this->persist($id); }
    protected function persist(?int $id)
    {
        $spec = $this->spec();
        $model = $this->model();
        $row = $id ? $this->record($model, $id) : null;
        if ($row && $row['is_archived']) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        $fields = array_keys($spec['fields']);
        if ($this->resource === 'staff') { $fields[] = 'password_confirmation'; }
        $data = $this->input($fields);
        $url = $this->resource.($id ? '/'.$id.'/edit' : '/create');
        $rules = match ($this->resource) {
            'products' => ['name' => 'required|max_length[100]', 'price' => 'required|regex_match[/^[0-9]{1,8}(\.[0-9]{1,2})?$/]|greater_than[0]',
                'stock_quantity' => 'required|is_natural|less_than_equal_to[2147483647]'],
            'customers' => ['full_name' => 'required|max_length[100]', 'email' => 'required|valid_email|max_length[100]', 'phone' => 'permit_empty|max_length[20]'],
            'staff' => ['username' => 'required|regex_match[/^[a-z0-9._-]{3,50}$/]|is_unique[users.username,id,'.($id ?? 0).']',
                'full_name' => 'required|max_length[100]', 'password' => ($id ? 'permit_empty' : 'required').'|min_length[8]|max_length[72]',
                'password_confirmation' => ($id ? 'permit_empty' : 'required').'|matches[password]'],
        };
        if ($this->resource === 'staff' && is_string($data['username'])) { $data['username'] = strtolower($data['username']); }
        if (! $this->validateData($data, $rules)) { return $this->invalid($url, $this->validator->getErrors(), $data); }
        if ($this->resource === 'staff' && strlen($data['password'] ?? '') > 72) { return $this->invalid($url, ['password' => 'Use a password of at most 72 bytes.'], $data); }
        $newImage = null;
        $images = new ImageStore();
        try {
            if ($spec['image']) { $newImage = $images->prepare($this->request->getFile('picture'), $spec['kind']); }
            $save = $data;
            unset($save['password_confirmation']);
            if ($this->resource === 'staff') {
                if (empty($save['password'])) { unset($save['password']); }
                else { $save['password'] = password_hash($save['password'], PASSWORD_DEFAULT); }
            }
            if ($newImage) { $save[$spec['image']] = $newImage; }
            $saved = $id ? $model->update($id, $save) : $model->insert($save);
            if (! $saved) { throw new \RuntimeException('The record could not be saved.'); }
        } catch (\DomainException $exception) {
            $images->delete($newImage);
            return $this->invalid($url, ['picture' => $exception->getMessage()], $data);
        } catch (\Throwable $exception) {
            $images->delete($newImage);
            log_message('error', 'Record save failed: {message}', ['message' => $exception->getMessage()]);
            return $this->invalid($url, ['record' => 'This record could not be saved. Please try again.'], $data);
        }
        if ($newImage && $row) { $images->delete($row[$spec['image']] ?? null); }
        return redirect()->to(site_url($this->resource))->with('success', $spec['singular'].($id ? ' updated.' : ' created.'));
    }
    public function remove(int $id)
    {
        return view('manage/remove', ['title' => 'Remove '.$this->spec()['singular'], 'row' => $this->record($this->model(), $id), 'spec' => $this->spec(), 'resource' => $this->resource]);
    }
    public function destroy(int $id)
    {
        $spec = $this->spec();
        $row = $this->record($this->model(), $id);
        if ($this->resource === 'staff' && $id === (int) session()->get('staff_id')) {
            return redirect()->to(site_url('staff'))->with('error', 'You cannot remove your own signed-in account.');
        }
        if ($this->request->getPost('confirm_remove') !== '1') {
            return $this->invalid($this->resource.'/'.$id.'/remove', ['confirm' => 'Tick the confirmation checkbox before continuing.']);
        }
        $foreign = match ($this->resource) { 'products' => 'product_id', 'customers' => 'customer_id', 'staff' => 'sold_by' };
        $hasSales = db_connect()->table('sales')->where($foreign, $id)->countAllResults() > 0;
        if ($this->resource === 'products' || $hasSales) {
            $this->model()->update($id, ['is_archived' => 1]);
            $message = $spec['singular'].' archived. Existing sales remain intact.';
        } else {
            try { $this->model()->delete($id); }
            catch (\Throwable $exception) { return redirect()->to(site_url($this->resource))->with('error', 'This record is now used by a sale and cannot be deleted.'); }
            if ($spec['image']) { (new ImageStore())->delete($row[$spec['image']] ?? null); }
            $message = $spec['singular'].' deleted.';
        }
        return redirect()->to(site_url($this->resource))->with('success', $message);
    }
}
