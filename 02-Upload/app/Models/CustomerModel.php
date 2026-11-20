<?php
namespace App\Models;
use CodeIgniter\Model;
class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $allowedFields = ['full_name', 'email', 'phone', 'is_archived'];
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $dateFormat = 'datetime';
}
