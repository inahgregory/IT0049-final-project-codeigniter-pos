<?php
use App\Libraries\Money;
use App\Libraries\SaleRecorder;
use App\Database\Seeds\DemoSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class PosTest extends CIUnitTestCase
{
    use DatabaseTestTrait, FeatureTestTrait;
    protected $namespace = 'App';
    protected $seed = DemoSeeder::class;
    protected $refresh = true;
    private int $staffId;

    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'form', 'pos']);
        $this->db->table('users')->insert(['username' => 'test.cashier', 'full_name' => 'Test Cashier',
            'password' => password_hash('test-password', PASSWORD_BCRYPT, ['cost' => 4]), 'created_at' => date('Y-m-d H:i:s')]);
        $this->staffId = (int) $this->db->insertID();
    }
    private function sendForm(string $url, array $data, bool $authenticated = true)
    {
        $security = service('security');
        $security->generateHash();
        $token = $security->getHash();
        $name = $security->getTokenName();
        $values = [$name => $token];
        if ($authenticated) { $values['staff_id'] = $this->staffId; $values['staff_name'] = 'Test Cashier'; }
        return $this->withSession($values)->post($url, $data + [$name => $token]);
    }
    private function product(): array { return $this->db->table('products')->where('name', 'Classic Notebook')->get()->getRowArray(); }

    public function testMoneyUsesIntegerCents(): void
    {
        $this->assertSame(8550, Money::cents('85.50'));
        $this->assertSame(10, Money::cents('0.1'));
        $this->assertSame('256.50', Money::decimal(Money::cents('85.50') * 3));
        $this->expectException(DomainException::class);
        Money::cents('1.234');
    }

    public function testSeederIsRepeatableAndKeepsAllRequiredRelationships(): void
    {
        $this->seed(DemoSeeder::class);
        $this->assertSame(6, $this->db->table('products')->countAllResults());
        $this->assertSame(5, $this->db->table('customers')->countAllResults());
        $foreign = $this->db->query('PRAGMA foreign_key_list(sales)')->getResultArray();
        $this->assertCount(3, $foreign);
    }

    public function testAllManagementPagesRedirectGuests(): void
    {
        foreach (['/', 'products', 'products/create', 'customers', 'customers/create', 'staff',
            'staff/create', 'sales', 'sales/create', 'products/1/edit', 'customers/1/remove'] as $url) {
            $this->withSession([])->get($url)->assertRedirectTo(site_url('login'));
        }
        $this->withSession([])->get('about')->assertOK();
    }

    public function testLoginAndLogoutAndFirstSetupIsClosed(): void
    {
        $this->sendForm('login', ['username' => 'test.cashier', 'password' => 'test-password'], false)
            ->assertRedirectTo(site_url('/'));
        $this->assertSame($this->staffId, session()->get('staff_id'));
        $this->withSession(['staff_id' => $this->staffId])->get('products')->assertOK();
        $this->sendForm('logout', [])->assertRedirectTo(site_url('login'));
        $this->withSession([])->get('products')->assertRedirectTo(site_url('login'));
        $this->sendForm('setup', ['username' => 'other', 'full_name' => 'Other Staff', 'password' => 'password-123',
            'password_confirmation' => 'password-123'], false)->assertStatus(403);
    }

    public function testInvalidLoginDoesNotPreservePassword(): void
    {
        $response = $this->sendForm('login', ['username' => 'test.cashier', 'password' => 'wrong-secret'], false);
        $response->assertRedirectTo(site_url('login'));
        $this->assertArrayNotHasKey('password', session()->getFlashdata('form_old'));
        $this->assertNull(session()->get('staff_id'));
    }

    public function testCustomerCrudAndValidation(): void
    {
        $this->sendForm('customers', ['full_name' => 'Remember Customer', 'email' => 'invalid', 'phone' => '123'])
            ->assertRedirectTo(site_url('customers/create'));
        $this->assertSame('Remember Customer', session()->getFlashdata('form_old')['full_name']);
        $this->sendForm('customers', ['full_name' => 'New Customer', 'email' => 'new@example.com', 'phone' => '123'])
            ->assertRedirectTo(site_url('customers'));
        $customer = $this->db->table('customers')->where('email', 'new@example.com')->get()->getRowArray();
        $this->withSession(['staff_id' => $this->staffId])->get('customers/'.$customer['id'].'/edit')->assertSeeInField('full_name', 'New Customer');
        $this->sendForm('customers/'.$customer['id'], ['full_name' => 'Updated Customer', 'email' => 'new@example.com', 'phone' => '456'])
            ->assertRedirectTo(site_url('customers'));
        $this->assertSame('Updated Customer', $this->db->table('customers')->where('id', $customer['id'])->get()->getRowArray()['full_name']);
        $this->sendForm('customers/'.$customer['id'].'/delete', ['confirm_remove' => '1'])->assertRedirectTo(site_url('customers'));
        $this->assertSame(0, $this->db->table('customers')->where('id', $customer['id'])->countAllResults());
    }

    public function testProductsCanBeCreatedEditedAndArchived(): void
    {
        $this->sendForm('products', ['name' => 'Test Product', 'price' => '12.35', 'stock_quantity' => '7'])->assertRedirectTo(site_url('products'));
        $product = $this->db->table('products')->where('name', 'Test Product')->get()->getRowArray();
        $this->sendForm('products/'.$product['id'], ['name' => 'Updated Product', 'price' => '14.10', 'stock_quantity' => '9'])
            ->assertRedirectTo(site_url('products'));
        $this->sendForm('products/'.$product['id'].'/delete', ['confirm_remove' => '1'])->assertRedirectTo(site_url('products'));
        $this->assertSame(1, (int) $this->db->table('products')->where('id', $product['id'])->get()->getRowArray()['is_archived']);
        $this->withSession(['staff_id' => $this->staffId])->get('products')->assertDontSee('Updated Product');
    }

    public function testInvalidPriceAndNegativeStockAreRejected(): void
    {
        $count = $this->db->table('products')->countAllResults();
        $this->sendForm('products', ['name' => 'Bad Product', 'price' => '1.234', 'stock_quantity' => '-2'])
            ->assertRedirectTo(site_url('products/create'));
        $errors = session()->getFlashdata('errors');
        $this->assertArrayHasKey('price', $errors);
        $this->assertArrayHasKey('stock_quantity', $errors);
        $this->assertSame($count, $this->db->table('products')->countAllResults());
    }

    public function testStaffPasswordsAreHashedAndBlankEditKeepsHash(): void
    {
        $data = ['username' => 'new.cashier', 'full_name' => 'New Cashier', 'password' => 'private-test-password',
            'password_confirmation' => 'private-test-password'];
        $this->sendForm('staff', $data)->assertRedirectTo(site_url('staff'));
        $staff = $this->db->table('users')->where('username', 'new.cashier')->get()->getRowArray();
        $this->assertTrue(password_verify('private-test-password', $staff['password']));
        $this->sendForm('staff/'.$staff['id'], ['username' => 'new.cashier', 'full_name' => 'Changed Name',
            'password' => '', 'password_confirmation' => ''])->assertRedirectTo(site_url('staff'));
        $this->assertSame($staff['password'], $this->db->table('users')->where('id', $staff['id'])->get()->getRowArray()['password']);
        $this->sendForm('staff', $data)->assertRedirectTo(site_url('staff/create'));
        $this->assertArrayHasKey('username', session()->getFlashdata('errors'));
        $this->sendForm('staff/'.$staff['id'].'/delete', ['confirm_remove' => '1'])->assertRedirectTo(site_url('staff'));
        $this->assertSame(0, $this->db->table('users')->where('id', $staff['id'])->countAllResults());
    }

    public function testStaffCannotRemoveOwnAccount(): void
    {
        $this->sendForm('staff/'.$this->staffId.'/delete', ['confirm_remove' => '1'])->assertRedirectTo(site_url('staff'));
        $this->assertSame(1, $this->db->table('users')->where('id', $this->staffId)->countAllResults());
    }

    public function testSaleReducesStockAndCalculatesExactTotal(): void
    {
        $product = $this->product();
        $id = (new SaleRecorder($this->db))->record((int) $product['id'], 1, $this->staffId, 3, bin2hex(random_bytes(16)));
        $sale = $this->db->table('sales')->where('id', $id)->get()->getRowArray();
        $this->assertSame(27, (int) $this->product()['stock_quantity']);
        $this->assertSame('256.50', Money::decimal(Money::cents((string) $sale['total_price'])));
        $this->assertSame('Ana Reyes', $sale['customer_name']);
        $this->assertSame('Test Cashier', $sale['staff_name']);
    }

    public function testWalkInSaleAndDuplicateSubmissionOnlyReduceStockOnce(): void
    {
        $product = $this->product();
        $token = bin2hex(random_bytes(16));
        $recorder = new SaleRecorder($this->db);
        $id = $recorder->record((int) $product['id'], null, $this->staffId, 2, $token);
        $this->assertSame($id, $recorder->record((int) $product['id'], null, $this->staffId, 2, $token));
        $this->assertSame(28, (int) $this->product()['stock_quantity']);
        $this->assertSame(1, $this->db->table('sales')->countAllResults());
        $this->assertNull($this->db->table('sales')->where('id', $id)->get()->getRowArray()['customer_id']);
    }

    public function testOversellingIsRejectedWithoutMutation(): void
    {
        try {
            (new SaleRecorder($this->db))->record((int) $this->product()['id'], null, $this->staffId, 31, bin2hex(random_bytes(16)));
            $this->fail('Overselling should fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Insufficient stock', $exception->getMessage());
        }
        $this->assertSame(30, (int) $this->product()['stock_quantity']);
        $this->assertSame(0, $this->db->table('sales')->countAllResults());
    }

    public function testRemainingStockCannotBeSoldTwiceAndExactStockCanBeSold(): void
    {
        $recorder = new SaleRecorder($this->db);
        $id = (int) $this->product()['id'];
        $recorder->record($id, null, $this->staffId, 29, bin2hex(random_bytes(16)));
        try { $recorder->record($id, null, $this->staffId, 2, bin2hex(random_bytes(16))); $this->fail('Only one is left.'); }
        catch (DomainException $exception) { $this->assertStringContainsString('Only 1', $exception->getMessage()); }
        $recorder->record($id, null, $this->staffId, 1, bin2hex(random_bytes(16)));
        $this->assertSame(0, (int) $this->product()['stock_quantity']);
        $this->assertSame(2, $this->db->table('sales')->countAllResults());
    }

    public function testSaleControllerIgnoresClientPriceAndStaffIdentity(): void
    {
        $this->sendForm('sales', ['product_id' => (string) $this->product()['id'], 'customer_id' => '',
            'quantity' => '2', 'sale_token' => bin2hex(random_bytes(16)), 'total_price' => '0.01', 'sold_by' => '999'])
            ->assertRedirect();
        $sale = $this->db->table('sales')->get()->getRowArray();
        $this->assertNotNull($sale);
        $this->assertSame($this->staffId, (int) $sale['sold_by']);
        $this->assertSame(17100, Money::cents((string) $sale['total_price']));
        $this->withSession(['staff_id' => $this->staffId])->get('sales')->assertSee('Classic Notebook');
        $this->withSession(['staff_id' => $this->staffId])->get('sales/'.$sale['id'])->assertSee('Receipt #'.$sale['id']);
    }

    public function testSaleInsertFailureRollsBackStock(): void
    {
        $this->db->query("CREATE TRIGGER fail_sale_insert BEFORE INSERT ON sales BEGIN SELECT RAISE(ABORT, 'Simulated insert failure'); END");
        try {
            (new SaleRecorder($this->db))->record((int) $this->product()['id'], null, $this->staffId, 2, bin2hex(random_bytes(16)));
            $this->fail('Insert should fail.');
        } catch (Throwable $exception) {
            $this->assertStringContainsString('Simulated', $exception->getMessage());
        }
        $this->assertSame(30, (int) $this->product()['stock_quantity']);
        $this->assertSame(0, $this->db->table('sales')->countAllResults());
    }

    public function testHistoricalNamesAndPricesSurviveEditsAndRemoval(): void
    {
        $product = $this->product();
        $id = (new SaleRecorder($this->db))->record((int) $product['id'], 1, $this->staffId, 1, bin2hex(random_bytes(16)));
        $this->db->table('products')->where('id', $product['id'])->update(['name' => 'Renamed', 'price' => '999.00']);
        $this->sendForm('products/'.$product['id'].'/delete', ['confirm_remove' => '1']);
        $this->sendForm('customers/1/delete', ['confirm_remove' => '1']);
        $sale = $this->db->table('sales')->where('id', $id)->get()->getRowArray();
        $this->assertSame('Classic Notebook', $sale['product_name']);
        $this->assertSame(8550, Money::cents((string) $sale['unit_price']));
        $this->assertSame('Ana Reyes', $sale['customer_name']);
        $this->assertSame(1, (int) $this->db->table('customers')->where('id', 1)->get()->getRowArray()['is_archived']);
    }

    public function testArchivedProductAndInvalidQuantityCannotBeSold(): void
    {
        $product = $this->product();
        $this->db->table('products')->where('id', $product['id'])->update(['is_archived' => 1]);
        $this->expectException(DomainException::class);
        (new SaleRecorder($this->db))->record((int) $product['id'], null, $this->staffId, 1, bin2hex(random_bytes(16)));
    }

    public function testArchivedStaffSessionIsRejected(): void
    {
        $this->db->table('users')->where('id', $this->staffId)->update(['is_archived' => 1]);
        $this->withSession(['staff_id' => $this->staffId])->get('products')->assertRedirectTo(site_url('login'));
    }

    public function testAuthenticatedManagementPagesRender(): void
    {
        foreach (['/', 'products', 'products/create', 'products/1/edit', 'products/1/remove',
            'customers', 'customers/create', 'customers/1/edit', 'staff', 'staff/create',
            'staff/'.$this->staffId.'/edit', 'sales', 'sales/create'] as $url) {
            $this->withSession(['staff_id' => $this->staffId])->get($url)->assertOK();
        }
    }

    public function testFirstStaffSetupHashesPasswordAndClosesAfterCreation(): void
    {
        $this->db->table('users')->where('id', $this->staffId)->delete();
        config('Pos')->allowSetup = true;
        $this->withSession([])->get('setup')->assertOK();
        $data = ['username' => 'first.staff', 'full_name' => 'First Staff', 'password' => 'private-first-password',
            'password_confirmation' => 'private-first-password'];
        $this->sendForm('setup', $data, false)->assertRedirectTo(site_url('/'));
        $staff = $this->db->table('users')->get()->getRowArray();
        $this->assertTrue(password_verify('private-first-password', $staff['password']));
        $this->assertNotSame('private-first-password', $staff['password']);
        $this->sendForm('setup', $data, false)->assertStatus(403);
        $this->assertSame(1, $this->db->table('users')->countAllResults());
    }

    public function testMissingCsrfCannotWriteARecord(): void
    {
        $before = $this->db->table('customers')->countAllResults();
        try {
            $this->withSession(['staff_id' => $this->staffId])->post('customers', [
                'full_name' => 'No Token', 'email' => 'no-token@example.com', 'phone' => '',
            ])->assertStatus(403);
        } catch (\CodeIgniter\Security\Exceptions\SecurityException $exception) {
            $this->assertStringContainsString('not allowed', $exception->getMessage());
        }
        $this->assertSame($before, $this->db->table('customers')->countAllResults());
    }

    public function testImagesBecomePreparedJpegCopiesWithSafePaths(): void
    {
        $source = tempnam(WRITEPATH.'cache', 'pos-image-');
        $image = imagecreatetruecolor(800, 600);
        imagefill($image, 0, 0, imagecolorallocate($image, 26, 110, 89));
        imagepng($image, $source);
        imagedestroy($image);
        $store = new \App\Libraries\ImageStore();
        $paths = [];
        try {
            foreach (['products' => [640, 480], 'avatars' => [256, 256]] as $kind => [$width, $height]) {
                $path = $store->preparePath($source, $kind);
                $paths[] = $path;
                $this->assertMatchesRegularExpression('~^'.$kind.'/[a-f0-9]{32}\\.jpg$~', $path);
                $size = getimagesize(WRITEPATH.'uploads/'.$path);
                $this->assertSame($width, $size[0]);
                $this->assertSame($height, $size[1]);
                $this->assertSame('image/jpeg', $size['mime']);
            }
        } finally {
            foreach ($paths as $path) { $store->delete($path); }
            unlink($source);
        }
    }

    public function testInvalidAndOversizedImageUploadsAreRejected(): void
    {
        $source = tempnam(WRITEPATH.'cache', 'pos-invalid-');
        file_put_contents($source, 'This is not an image.');
        $store = new \App\Libraries\ImageStore();
        try {
            foreach ([20, 2097153] as $bytes) {
                $upload = $this->getMockBuilder(\CodeIgniter\HTTP\Files\UploadedFile::class)
                    ->setConstructorArgs([$source, 'fake.png', 'image/png', $bytes, UPLOAD_ERR_OK])
                    ->onlyMethods(['isValid'])->getMock();
                $upload->method('isValid')->willReturn(true);
                try { $store->prepare($upload, 'avatars'); $this->fail('Invalid upload should fail.'); }
                catch (DomainException $exception) { $this->assertNotEmpty($exception->getMessage()); }
            }
        } finally { unlink($source); }
    }

    public function testSaleQuantitiesMustBePositiveWholeNumbers(): void
    {
        foreach (['0', '-1', '1.5'] as $quantity) {
            $this->sendForm('sales', ['product_id' => (string) $this->product()['id'],
                'customer_id' => '', 'quantity' => $quantity, 'sale_token' => bin2hex(random_bytes(16))])
                ->assertRedirectTo(site_url('sales/create'));
            $this->assertArrayHasKey('quantity', session()->getFlashdata('errors'));
        }
        $this->assertSame(30, (int) $this->product()['stock_quantity']);
        $this->assertSame(0, $this->db->table('sales')->countAllResults());
    }

    public function testMaximumDecimalTotalIsEnforcedBeforeStockChanges(): void
    {
        $product = $this->product();
        $this->db->table('products')->where('id', $product['id'])->update(['price' => '99999999.99']);
        try {
            (new SaleRecorder($this->db))->record((int) $product['id'], null, $this->staffId, 2, bin2hex(random_bytes(16)));
            $this->fail('Total would exceed DECIMAL(10,2).');
        } catch (DomainException $exception) { $this->assertStringContainsString('supported total', $exception->getMessage()); }
        $this->assertSame(30, (int) $this->product()['stock_quantity']);
    }

    public function testRemovingStaffUsedByASaleArchivesItAndPreservesHistory(): void
    {
        $originalId = $this->staffId;
        (new SaleRecorder($this->db))->record((int) $this->product()['id'], null, $originalId, 1, bin2hex(random_bytes(16)));
        $this->db->table('users')->insert(['username' => 'other.staff', 'full_name' => 'Other Staff',
            'password' => password_hash('test-password', PASSWORD_BCRYPT, ['cost' => 4]), 'created_at' => date('Y-m-d H:i:s')]);
        $this->staffId = (int) $this->db->insertID();
        $this->sendForm('staff/'.$originalId.'/delete', ['confirm_remove' => '1'])->assertRedirectTo(site_url('staff'));
        $this->assertSame(1, (int) $this->db->table('users')->where('id', $originalId)->get()->getRowArray()['is_archived']);
        $this->assertSame('Test Cashier', $this->db->table('sales')->get()->getRowArray()['staff_name']);
        $this->withSession(['staff_id' => $originalId])->get('sales')->assertRedirectTo(site_url('login'));
    }
}

