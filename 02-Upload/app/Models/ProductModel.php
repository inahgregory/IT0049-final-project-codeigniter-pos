<?php
namespace App\Models;
use CodeIgniter\Model;
class ProductModel extends Model
{
    protected $table = 'products';
    protected $allowedFields = ['name', 'price', 'stock_quantity', 'image', 'is_archived'];
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $dateFormat = 'datetime';
}
