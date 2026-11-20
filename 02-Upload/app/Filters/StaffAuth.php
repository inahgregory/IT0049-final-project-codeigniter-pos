<?php
namespace App\Filters;
use App\Models\StaffModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
class StaffAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $id = session()->get('staff_id');
        $staff = $id ? (new StaffModel())->find($id) : null;
        if (! $staff || $staff['is_archived']) {
            session()->remove(['staff_id', 'staff_name']);
            return redirect()->to(site_url('login'))->with('error', 'Please log in to continue.');
        }
        session()->set('staff_name', $staff['full_name']);
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('Cache-Control', 'no-store, private');
    }
}
