<?php
namespace App\Controllers;
class Dashboard extends BaseController
{
    public function index()
    {
        $db = db_connect();
        return view('dashboard', ['title' => 'Dashboard',
            'productCount' => $db->table('products')->where('is_archived', 0)->countAllResults(),
            'customerCount' => $db->table('customers')->where('is_archived', 0)->countAllResults(),
            'saleCount' => $db->table('sales')->countAllResults(),
            'revenue' => $db->table('sales')->selectSum('total_price', 'total')->get()->getRowArray()['total'] ?? 0,
            'lowStock' => $db->table('products')->where('is_archived', 0)->where('stock_quantity <=', 5)->get()->getResultArray(),
            'latestSales' => $db->table('sales')->orderBy('id', 'DESC')->limit(5)->get()->getResultArray(),
        ]);
    }
    public function about() { return view('about', ['title' => 'About']); }
}
