<?php
namespace App\Models;
use CodeIgniter\Model;
class SaleModel extends Model
{
    protected $table = 'sales';
    protected $allowedFields = ['product_id', 'customer_id', 'sold_by', 'quantity', 'unit_price', 'total_price', 'product_name', 'customer_name', 'staff_name', 'sale_token'];
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $dateFormat = 'datetime';
}
