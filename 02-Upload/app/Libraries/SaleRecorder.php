<?php
namespace App\Libraries;
use CodeIgniter\Database\BaseConnection;
class SaleRecorder
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }
    public function record(int $productId, ?int $customerId, int $staffId, int $quantity, string $token): int
    {
        if ($quantity < 1 || $quantity > 2147483647 || ! preg_match('/^[a-f0-9]{32}$/D', $token)) {
            throw new \DomainException('Enter a valid quantity and submit the sale form again.');
        }
        $db = $this->db;
        $db->transException(true)->transBegin();
        try {
            $existing = $db->table('sales')->where('sale_token', $token)->get()->getRowArray();
            if ($existing) {
                if ((int) $existing['sold_by'] !== $staffId) { throw new \DomainException('Please open a new sale form.'); }
                $db->transCommit();
                return (int) $existing['id'];
            }
            $product = $db->table('products')->where('id', $productId)->where('is_archived', 0)->get()->getRowArray();
            $staff = $db->table('users')->where('id', $staffId)->where('is_archived', 0)->get()->getRowArray();
            $customer = $customerId ? $db->table('customers')->where('id', $customerId)->where('is_archived', 0)->get()->getRowArray() : null;
            if (! $product || ! $staff || ($customerId && ! $customer)) { throw new \DomainException('The selected product, customer, or staff account is no longer available.'); }
            if ((int) $product['stock_quantity'] < $quantity) {
                throw new \DomainException('Insufficient stock. Only '.$product['stock_quantity'].' unit(s) are available.');
            }
            $unitCents = Money::cents((string) $product['price']);
            if ($unitCents < 1 || $quantity > intdiv(Money::MAX_CENTS, $unitCents)) {
                throw new \DomainException('This sale exceeds the supported total. Use a smaller quantity.');
            }
            // A conditional update prevents another sale from taking the same stock.
            $db->table('products')->where('id', $productId)->where('is_archived', 0)
                ->where('stock_quantity >=', $quantity)->where('price', $product['price'])
                ->set('stock_quantity', 'stock_quantity - '.$quantity, false)->update();
            if ($db->affectedRows() !== 1) { throw new \DomainException('Stock or price changed. Review availability and try again.'); }
            $saved = $db->table('sales')->insert([
                'product_id' => $productId, 'customer_id' => $customerId, 'sold_by' => $staffId,
                'quantity' => $quantity, 'unit_price' => Money::decimal($unitCents),
                'total_price' => Money::decimal($unitCents * $quantity),
                'product_name' => $product['name'], 'customer_name' => $customer['full_name'] ?? null,
                'staff_name' => $staff['full_name'], 'sale_token' => $token, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            if (! $saved || ! $db->transStatus()) { throw new \RuntimeException('The sale could not be saved.'); }
            $id = (int) $db->insertID();
            if (! $db->transCommit()) { throw new \RuntimeException('The sale could not be committed.'); }
            return $id;
        } catch (\Throwable $exception) {
            $db->transRollback();
            $db->resetTransStatus();
            throw $exception;
        }
    }
}
