<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreatePosTables extends Migration
{
    public function up()
    {
        $id = ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true];
        $created = ['type' => 'DATETIME'];
        $archived = ['type' => 'BOOLEAN', 'default' => false];
        $attributes = $this->db->DBDriver === 'MySQLi' ? ['ENGINE' => 'InnoDB'] : [];
        $this->forge->addField([
            'id' => $id, 'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'price' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'stock_quantity' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_archived' => $archived, 'created_at' => $created,
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('products', true, $attributes);
        $this->forge->addField([
            'id' => $id, 'full_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'email' => ['type' => 'VARCHAR', 'constraint' => 100],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'is_archived' => $archived, 'created_at' => $created,
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('customers', true, $attributes);
        $this->forge->addField([
            'id' => $id, 'username' => ['type' => 'VARCHAR', 'constraint' => 50],
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'password' => ['type' => 'VARCHAR', 'constraint' => 255],
            'avatar' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_archived' => $archived, 'created_at' => $created,
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('users', true, $attributes);
        $this->forge->addField([
            'id' => $id, 'product_id' => ['type' => 'INT', 'unsigned' => true],
            'customer_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'sold_by' => ['type' => 'INT', 'unsigned' => true],
            'quantity' => ['type' => 'INT', 'unsigned' => true],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'total_price' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'product_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'customer_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'staff_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'sale_token' => ['type' => 'VARCHAR', 'constraint' => 32],
            'created_at' => $created,
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('sale_token');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('sold_by', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('sales', true, $attributes);
    }
    public function down()
    {
        foreach (['sales', 'users', 'customers', 'products'] as $table) { $this->forge->dropTable($table, true); }
    }
}
