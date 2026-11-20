<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;
class DemoSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        if ($this->db->table('products')->countAllResults() === 0) {
            $this->db->table('products')->insertBatch([
                ['name' => 'Classic Notebook', 'price' => '85.50', 'stock_quantity' => 30, 'created_at' => $now],
                ['name' => 'Gel Pen Set', 'price' => '120.00', 'stock_quantity' => 25, 'created_at' => $now],
                ['name' => 'Canvas Tote Bag', 'price' => '249.75', 'stock_quantity' => 15, 'created_at' => $now],
                ['name' => 'Desk Organizer', 'price' => '175.00', 'stock_quantity' => 12, 'created_at' => $now],
                ['name' => 'Sticky Notes', 'price' => '45.25', 'stock_quantity' => 40, 'created_at' => $now],
                ['name' => 'Water Bottle', 'price' => '325.00', 'stock_quantity' => 0, 'created_at' => $now],
            ]);
        }
        if ($this->db->table('customers')->countAllResults() === 0) {
            $this->db->table('customers')->insertBatch([
                ['full_name' => 'Ana Reyes', 'email' => 'ana.reyes@example.com', 'phone' => '0917 555 0101', 'created_at' => $now],
                ['full_name' => 'Marco Santos', 'email' => 'marco.santos@example.com', 'phone' => '0917 555 0102', 'created_at' => $now],
                ['full_name' => 'Sofia Cruz', 'email' => 'sofia.cruz@example.com', 'phone' => '0917 555 0103', 'created_at' => $now],
                ['full_name' => 'Luis Garcia', 'email' => 'luis.garcia@example.com', 'phone' => '0917 555 0104', 'created_at' => $now],
                ['full_name' => 'Mia Torres', 'email' => 'mia.torres@example.com', 'phone' => '0917 555 0105', 'created_at' => $now],
            ]);
        }
        // No shared staff password is seeded. Create the first account at /setup.
    }
}
