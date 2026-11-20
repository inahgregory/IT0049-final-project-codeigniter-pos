<?php
namespace App\Controllers;
use App\Models\StaffModel;
class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('staff_id')) { return redirect()->to(site_url('/')); }
        return view('auth/login', ['title' => 'Log in', 'canSetup' => config('Pos')->allowSetup && (new StaffModel())->countAllResults() === 0]);
    }
    public function attempt()
    {
        $data = $this->input(['username', 'password']);
        if (! $this->validateData($data, ['username' => 'required|max_length[50]', 'password' => 'required|max_length[72]'])) {
            return $this->invalid('login', $this->validator->getErrors(), $data);
        }
        $username = strtolower($data['username']);
        if (! service('throttler')->check('pos_login_'.hash('sha256', $username.'|'.$this->request->getIPAddress()), 5, 60)) {
            return $this->invalid('login', ['login' => 'Too many attempts. Please wait a minute and try again.'], $data);
        }
        $staff = (new StaffModel())->where('username', $username)->where('is_archived', 0)->first();
        if (! $staff || strlen($data['password']) > 72 || ! password_verify($data['password'], $staff['password'])) {
            return $this->invalid('login', ['login' => 'The username or password is incorrect.'], $data);
        }
        session()->regenerate(true);
        session()->set(['staff_id' => (int) $staff['id'], 'staff_name' => $staff['full_name']]);
        return redirect()->to(site_url('/'))->with('success', 'Welcome back, '.$staff['full_name'].'.');
    }
    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('login'));
    }
    public function setup()
    {
        if ((new StaffModel())->countAllResults() > 0) { return redirect()->to(site_url('login')); }
        if (! config('Pos')->allowSetup) { return $this->response->setStatusCode(403)->setBody('First-staff setup is disabled. Contact the administrator.'); }
        return view('auth/setup', ['title' => 'Create first staff account']);
    }
    public function createFirst()
    {
        $model = new StaffModel();
        if (! config('Pos')->allowSetup || $model->countAllResults() > 0) { return $this->response->setStatusCode(403)->setBody('First-staff setup is closed.'); }
        $data = $this->input(['username', 'full_name', 'password', 'password_confirmation']);
        if (is_string($data['username'])) { $data['username'] = strtolower($data['username']); }
        $rules = [
            'username' => 'required|regex_match[/^[a-z0-9._-]{3,50}$/]|is_unique[users.username]',
            'full_name' => 'required|max_length[100]', 'password' => 'required|min_length[8]|max_length[72]',
            'password_confirmation' => 'required|matches[password]',
        ];
        if (! $this->validateData($data, $rules)) { return $this->invalid('setup', $this->validator->getErrors(), $data); }
        if (strlen($data['password']) > 72) { return $this->invalid('setup', ['password' => 'Use a password of at most 72 bytes.'], $data); }
        $id = $model->insert(['username' => $data['username'], 'full_name' => $data['full_name'], 'password' => password_hash($data['password'], PASSWORD_DEFAULT)]);
        session()->regenerate(true);
        session()->set(['staff_id' => (int) $id, 'staff_name' => $data['full_name']]);
        return redirect()->to(site_url('/'))->with('success', 'Your first staff account is ready.');
    }
}
