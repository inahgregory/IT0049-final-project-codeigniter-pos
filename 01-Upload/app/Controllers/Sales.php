<?php
namespace App\Controllers;
use App\Libraries\SaleRecorder;
use App\Models\ProductModel;
use App\Models\CustomerModel;
use App\Models\SaleModel;
class Sales extends BaseController
{
    public function index()
    {
        $sales = (new SaleModel())->orderBy('id', 'DESC')->findAll();
        return view('sales/index', ['title' => 'Sales History', 'sales' => $sales]);
    }
    public function create()
    {
        return view('sales/form', [
            'title' => 'Record Sale', 'products' => (new ProductModel())->where('is_archived', 0)->orderBy('name')->findAll(),
            'customers' => (new CustomerModel())->where('is_archived', 0)->orderBy('full_name')->findAll(),
            'saleToken' => bin2hex(random_bytes(16)),
        ]);
    }
    public function store()
    {
        $data = $this->input(['product_id', 'customer_id', 'quantity', 'sale_token']);
        $rules = ['product_id' => 'required|is_natural_no_zero',
            'customer_id' => 'permit_empty|is_natural_no_zero',
            'quantity' => 'required|is_natural_no_zero|less_than_equal_to[2147483647]',
            'sale_token' => 'required|regex_match[/^[a-f0-9]{32}$/]'];
        if (! $this->validateData($data, $rules)) { return $this->invalid('sales/create', $this->validator->getErrors(), $data); }
        try {
            $id = (new SaleRecorder())->record((int) $data['product_id'], $data['customer_id'] ? (int) $data['customer_id'] : null,
                (int) session()->get('staff_id'), (int) $data['quantity'], $data['sale_token']);
        } catch (\DomainException $exception) {
            return $this->invalid('sales/create', ['sale' => $exception->getMessage()], $data);
        } catch (\Throwable $exception) {
            log_message('error', 'Sale failed: {message}', ['message' => $exception->getMessage()]);
            return $this->invalid('sales/create', ['sale' => 'The sale could not be completed. Stock was not changed. Please review and try again.'], $data);
        }
        return redirect()->to(site_url('sales/'.$id))->with('success', 'Sale recorded successfully. Inventory has been updated.');
    }
    public function show(int $id)
    {
        return view('sales/show', ['title' => 'Sale receipt', 'sale' => $this->record(new SaleModel(), $id)]);
    }
}
