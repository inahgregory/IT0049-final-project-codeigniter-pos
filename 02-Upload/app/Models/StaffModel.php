<?php
namespace App\Models;
use CodeIgniter\Model;
class StaffModel extends Model
{
    protected $table = 'users';
    protected $allowedFields = ['username', 'full_name', 'password', 'avatar', 'is_archived'];
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $dateFormat = 'datetime';
}
