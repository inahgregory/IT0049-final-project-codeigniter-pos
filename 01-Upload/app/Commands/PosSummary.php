<?php
namespace App\Commands;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
class PosSummary extends BaseCommand
{
    protected $group = 'Counter POS';
    protected $name = 'pos:summary';
    protected $description = 'Show inventory, transaction counts, and schema evidence without credentials.';
    public function run(array $params)
    {
        $db = db_connect();
        CLI::write('Counter POS database summary', 'green');
        $rows = $db->table('products')->orderBy('id')->get()->getResultArray();
        CLI::table(array_map(static fn ($row) => [
            $row['id'], $row['name'], number_format((float) $row['price'], 2, '.', ''),
            $row['stock_quantity'], $row['is_archived'] ? 'yes' : 'no',
        ], $rows), ['ID', 'Product', 'Price', 'Stock', 'Archived']);
        CLI::write('Customers: '.$db->table('customers')->countAllResults());
        CLI::write('Staff: '.$db->table('users')->countAllResults());
        CLI::write('Sales: '.$db->table('sales')->countAllResults());
        CLI::write('Sales relationships: product_id -> products.id; customer_id -> customers.id; sold_by -> users.id');
    }
}
