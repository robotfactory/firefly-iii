<?php
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class TransactionControllerTest
 */
class TransactionControllerTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

    }

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }


    public function testCreate()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');


        // fake!
        $repository->shouldReceive('getAccounts')->andReturn(new Collection);


        $this->call('GET', '/transactions/create/withdrawal?account_id=12');
        $this->assertResponseOk();
    }

    public function testDelete()
    {
        $journal = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $this->be($journal->user);

        $this->call('GET', '/transaction/delete/' . $journal->id);
        $this->assertResponseOk();
    }

    public function testDestroy()
    {
        $journal = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $this->be($journal->user);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Journal\JournalRepositoryInterface');

        // fake!
        $repository->shouldReceive('delete')->andReturn(true);

        $this->call('POST', '/transaction/destroy/' . $journal->id, ['_token' => 'replaceMe']);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');

    }

    public function testEdit()
    {
        // make complete journal:
        $accountType  = FactoryMuffin::create('FireflyIII\Models\AccountType');
        $journal      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $account      = FactoryMuffin::create('FireflyIII\Models\Account');
        $transaction1 = FactoryMuffin::create('FireflyIII\Models\Transaction');
        $transaction2 = FactoryMuffin::create('FireflyIII\Models\Transaction');

        $accountType->type        = 'Asset account';
        $account->account_type_id = $accountType->id;

        $account->save();
        $transaction1->account_id             = $account->id;
        $transaction1->transaction_journal_id = $journal->id;
        $transaction1->save();

        $transaction2->account_id             = $account->id;
        $transaction2->transaction_journal_id = $journal->id;
        $transaction2->save();

        // also add some tags:
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');
        $tag->transactionJournals()->save($journal);

        // and a category and a budget:
        $budget   = FactoryMuffin::create('FireflyIII\Models\Budget');
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $category->transactionJournals()->save($journal);
        $budget->transactionJournals()->save($journal);

        // and a piggy bank event:
        $pbEvent                         = FactoryMuffin::create('FireflyIII\Models\PiggyBankEvent');
        $pbEvent->transaction_journal_id = $journal->id;
        $pbEvent->save();

        $this->be($journal->user);


        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');


        // fake!
        $repository->shouldReceive('getAccounts')->andReturn(new Collection);

        $this->call('GET', '/transaction/edit/' . $journal->id);
        $this->assertResponseOk();
    }

    public function testIndexRevenue()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Journal\JournalRepositoryInterface');

        // fake!
        $repository->shouldReceive('getJournalsOfTypes')->withArgs([['Deposit'], 0, 0])->andReturn(new LengthAwarePaginator(new Collection, 0, 50));

        $this->call('GET', '/transactions/deposit');
        $this->assertResponseOk();

    }

    public function testIndexTransfer()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Journal\JournalRepositoryInterface');

        // fake!
        $repository->shouldReceive('getJournalsOfTypes')->withArgs([['Transfer'], 0, 0])->andReturn(new LengthAwarePaginator(new Collection, 0, 50));

        $this->call('GET', '/transactions/transfers');
        $this->assertResponseOk();
    }

    public function testIndexWithdrawal()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Journal\JournalRepositoryInterface');

        // fake!
        $repository->shouldReceive('getJournalsOfTypes')->withArgs([['Withdrawal'], 0, 0])->andReturn(new LengthAwarePaginator(new Collection, 0, 50));

        $this->call('GET', '/transactions/withdrawal');
        $this->assertResponseOk();
    }

    public function testReorder()
    {
        $journal = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $this->be($journal->user);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Journal\JournalRepositoryInterface');

        // fake!
        $repository->shouldReceive('getWithDate')->withAnyArgs()->andReturn($journal);

        $data = [
            'items'  => [$journal->id],
            'date'   => $journal->date->format('Y-m-d'),
            '_token' => 'replaceMe'
        ];

        $this->call('POST', '/transaction/reorder', $data);
        $this->assertResponseOk();
    }

    public function testShow()
    {
        $journal                              = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $transaction1                         = FactoryMuffin::create('FireflyIII\Models\Transaction');
        $currency                             = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $transaction1->transaction_journal_id = $journal->id;
        $transaction1->save();
        $this->be($journal->user);


        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Journal\JournalRepositoryInterface');

        // fake!
        $repository->shouldReceive('getAmountBefore')->withAnyArgs()->andReturn(5);
        Amount::shouldReceive('getDefaultCurrency')->once()->andReturn($currency);
        Amount::shouldReceive('getAllCurrencies')->once()->andReturn([$currency]);
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');
        Amount::shouldReceive('formatTransaction')->andReturn('X');
        Amount::shouldReceive('format')->andReturn('X');


        $this->call('GET', '/transaction/show/' . $journal->id);
        $this->assertResponseOk();
    }

    public function testStore()
    {
        $account  = FactoryMuffin::create('FireflyIII\Models\Account');
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $journal  = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $this->be($account->user);

        $data = [
            'reminder_id'        => '',
            'what'               => 'withdrawal',
            'description'        => 'Bla bla bla',
            'account_id'         => $account->id,
            'expense_account'    => 'Bla bla',
            'amount'             => '100',
            'amount_currency_id' => $currency->id,
            'date'               => '2015-05-05',
            'budget_id'          => '0',
            'create_another'     => '1',
            'category'           => '',
            'tags'               => '',
            'piggy_bank_id'      => '0',
            '_token'             => 'replaceMe',
        ];

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Journal\JournalRepositoryInterface');

        // fake!
        $repository->shouldReceive('store')->andReturn($journal);
        $repository->shouldReceive('deactivateReminder')->andReturnNull();


        $this->call('POST', '/transactions/store/withdrawal', $data);

        //$this->assertSessionHas('errors','bla');
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');

    }


    public function testUpdate()
    {
        $account  = FactoryMuffin::create('FireflyIII\Models\Account');
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $journal  = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $this->be($journal->user);
        $account->user_id = $journal->user_id;
        $account->save();

        $data = [
            '_token'             => 'replaceMe',
            'id'                 => $journal->id,
            'what'               => 'withdrawal',
            'description'        => 'LunchX',
            'account_id'         => $account->id,
            'expense_account'    => 'Lunch House',
            'amount'             => '4.72',
            'amount_currency_id' => '1',
            'date'               => '2015-05-31',
            'budget_id'          => '0',
            'category'           => 'Lunch',
            'return_to_edit' => 1,
            'tags'               => '',
            'piggy_bank_id'      => '0',
        ];

        $this->call('POST', '/transactions/store/withdrawal', $data);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Journal\JournalRepositoryInterface');

        // fake!
        $repository->shouldReceive('update')->andReturn($journal);


        $this->call('POST', '/transaction/update/' . $journal->id, $data);
        //$this->assertSessionHas('errors','bla');
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');


    }

}