<?php

declare(strict_types=1);

namespace Tests\Packages\Wallet\Feature;

/**
 * @property \Database\ConnectionInterface $connection
 * @property WalletManagerService          $manager
 * @property FeeCalculatorService          $calculator
 * @property BalanceManagerService         $balances
 */

use Money\Money;
use Testing\Support\DatabaseTestHelper;
use Wallet\Enums\Currency;
use Wallet\Enums\TransactionStatus;
use Wallet\Enums\TransactionType;
use Wallet\Exceptions\CurrencyMismatchException;
use Wallet\Exceptions\DuplicateTransactionException;
use Wallet\Exceptions\InsufficientFundsException;
use Wallet\Exceptions\InvalidAmountException;
use Wallet\Models\Wallet;
use Wallet\Services\BalanceManagerService;
use Wallet\Services\FeeCalculatorService;
use Wallet\Services\TransactionManagerService;
use Wallet\Services\WalletManagerService;
use Wallet\Wallet as WalletFacade;

beforeEach(function () {
    // Setup Test Environment (Schema + Migrations)
    $this->connection = DatabaseTestHelper::setupTestEnvironment(['Wallet']);
    $this->bootPackage('Wallet');

    $this->manager = resolve(WalletManagerService::class);
    $this->calculator = resolve(FeeCalculatorService::class);
    $this->balances = resolve(BalanceManagerService::class);
});

afterEach(function () {
    DatabaseTestHelper::dropAllTables();
    DatabaseTestHelper::resetDefaultConnection();
});

describe('Wallet Creation', function () {
    test('can create a wallet', function () {
        $wallet = WalletFacade::createWallet()
            ->owner(1, 'User')
            ->currency(Currency::USD)
            ->create();

        expect($wallet)->toBeInstanceOf(Wallet::class)
            ->and($wallet->owner_id)->toBe(1)
            ->and($wallet->owner_type)->toBe('User')
            ->and($wallet->balance)->toBe(0)
            ->and($wallet->currency)->toBe(Currency::USD);
    });

    test('can find a wallet by owner', function () {
        $wallet = WalletFacade::createWallet()
            ->owner(1, 'User')
            ->currency(Currency::USD)
            ->create();

        $found = $this->manager->findByOwner(1, 'User', Currency::USD);

        expect($found->id)->toBe((int)$wallet->id);
    });
});

describe('Credit Operations', function () {
    test('can credit wallet', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        $tx = WalletFacade::transaction($wallet)
            ->credit(100)
            ->execute();

        expect($this->manager->toMoney($tx)->equals(Money::dollars(100)))->toBeTrue()
            ->and($tx->type)->toBe(TransactionType::CREDIT)
            ->and($this->manager->toMoney($tx, 'fee')->isZero())->toBeTrue()
            ->and($this->manager->toMoney($tx, 'net_amount')->equals(Money::dollars(100)))->toBeTrue();

        $wallet->refresh();
        expect($wallet->balance)->toBe(10000);
    });

    test('credit updates balance correctly', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        WalletFacade::transaction($wallet)->credit(50)->execute();
        WalletFacade::transaction($wallet)->credit(30)->execute();

        $balance = $this->manager->getBalance((int)$wallet->id);
        expect($balance)->toBeInstanceOf(Money::class)
            ->and($balance->equals(Money::dollars(80)))->toBeTrue();
    });
});

describe('Debit Operations', function () {
    test('can debit wallet with sufficient funds', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        // Credit first
        WalletFacade::transaction($wallet)->credit(100)->execute();

        // Debit
        $tx = WalletFacade::transaction($wallet)->debit(30)->execute();

        expect($this->manager->toMoney($tx)->equals(Money::dollars(30)))->toBeTrue()
            ->and($tx->type)->toBe(TransactionType::DEBIT)
            ->and($this->manager->toMoney($tx, 'net_amount')->equals(Money::dollars(-30)))->toBeTrue();

        $wallet->refresh();
        expect($wallet->balance)->toBe(7000); // $70 remaining
    });

    test('throws exception for insufficient funds', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        WalletFacade::transaction($wallet)->credit(50)->execute();

        // Try to debit more than available
        WalletFacade::transaction($wallet)->debit(100)->execute();
    })->throws(InsufficientFundsException::class);
});

describe('Fee Calculation', function () {
    test('calculates no fee when no rules', function () {
        $result = $this->calculator->calculate(TransactionType::CREDIT, Money::dollars(100), null);

        expect($result['fee']->isZero())->toBeTrue()
            ->and($result['net_amount']->equals(Money::dollars(100)))->toBeTrue();
    });

    test('calculates fixed fee', function () {
        WalletFacade::feeRule('Fixed Fee')
            ->credit()
            ->fixed(2) // $2
            ->currency(Currency::USD)
            ->save();

        $result = $this->calculator->credit(100);

        expect($result['fee']->equals(Money::dollars(2)))->toBeTrue()
            ->and($result['net_amount']->equals(Money::dollars(98)))->toBeTrue();
    });

    test('calculates percentage fee', function () {
        WalletFacade::feeRule('Percentage Fee')
            ->credit()
            ->percentage(2.9) // 2.9%
            ->currency(Currency::USD)
            ->save();

        $result = $this->calculator->credit(100);

        expect($result['fee']->equals(Money::dollars(2.90)))->toBeTrue();
    });

    test('calculates tiered fee (fixed + percentage)', function () {
        WalletFacade::feeRule('Tiered Fee')
            ->credit()
            ->tiered(0.30, 2.9) // $0.30 + 2.9%
            ->currency(Currency::USD)
            ->save();

        $result = $this->calculator->credit(100);

        // $0.30 + 2.9% of $100 = $0.30 + $2.90 = $3.20
        expect($result['fee']->equals(Money::dollars(3.20)))->toBeTrue();
    });

    test('respects min fee constraint', function () {
        WalletFacade::feeRule('Min Fee Rule')
            ->credit()
            ->percentage(2.9)
            ->minFee(1) // $1.00 minimum
            ->currency(Currency::USD)
            ->save();

        // 2.9% of $10 = $0.29, but min is $1.00
        $result = $this->calculator->credit(10);
        expect($result['fee']->equals(Money::dollars(1)))->toBeTrue();
    });

    test('respects max fee constraint', function () {
        WalletFacade::feeRule('Max Fee Rule')
            ->credit()
            ->percentage(10) // 10%
            ->maxFee(5) // $5.00 maximum
            ->currency(Currency::USD)
            ->save();

        // 10% of $100 = $10, but max is $5.00
        $result = $this->calculator->credit(100);
        expect($result['fee']->equals(Money::dollars(5)))->toBeTrue();
    });
});

describe('Refund Operations', function () {
    test('can refund a transaction', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        // Original credit
        $creditTx = WalletFacade::transaction($wallet)
            ->credit(100)
            ->reference('TXN_123')
            ->execute();

        // Refund
        $refundTx = $this->manager->refund('TXN_123');

        expect($refundTx->type)->toBe(TransactionType::REFUND)
            ->and($refundTx->parent_transaction_id)->toBe($creditTx->id)
            ->and($this->manager->toMoney($refundTx)->equals(Money::dollars(100)))->toBeTrue();

        $balance = $this->manager->getBalance((int)$wallet->id);
        expect($balance->isZero())->toBeTrue();
    });
});

describe('Transfer Operations', function () {
    test('can transfer between wallets', function () {
        $wallet1 = WalletFacade::createWallet()->owner(1, 'User')->create();
        $wallet2 = WalletFacade::createWallet()->owner(2, 'User')->create();

        // Fund first wallet
        WalletFacade::transaction($wallet1)->credit(100)->execute();

        // Transfer
        $result = $this->manager->transfer((int)$wallet1->id, (int)$wallet2->id, Money::dollars(30));

        expect($result)->toHaveKey('debit')
            ->toHaveKey('credit');

        $balance1 = $this->manager->getBalance((int)$wallet1->id);
        $balance2 = $this->manager->getBalance((int)$wallet2->id);

        expect($balance1->equals(Money::dollars(70)))->toBeTrue()
            ->and($balance2->equals(Money::dollars(30)))->toBeTrue();
    });
});

describe('Idempotency', function () {
    test('prevents duplicate transactions with same reference_id', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        WalletFacade::transaction($wallet)
            ->credit(100)
            ->reference('UNIQUE_REF_123')
            ->execute();

        // Try with same reference
        WalletFacade::transaction($wallet)
            ->credit(100)
            ->reference('UNIQUE_REF_123')
            ->execute();
    })->throws(DuplicateTransactionException::class);
});

describe('Currency Validation', function () {
    test('rejects mismatched currency', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->currency(Currency::USD)->create();

        // Try to credit with EUR
        WalletFacade::transaction($wallet)->credit(100, Currency::EUR)->execute();
    })->throws(CurrencyMismatchException::class);
});

describe('Balance Reconciliation', function () {
    test('reconciles balance from ledger', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        WalletFacade::transaction($wallet)->credit(100)->execute();
        WalletFacade::transaction($wallet)->credit(50)->execute();

        // Manually corrupt balance
        $wallet->update(['balance' => 9999]);

        // Reconcile should fix it
        $isBalanced = $this->balances->reconcile((int)$wallet->id);

        expect($isBalanced)->toBeFalse(); // Was unbalanced

        $wallet->refresh();
        expect($wallet->balance)->toBe(15000); // Fixed to $150
    });
});

describe('Integer Money Storage', function () {
    test('stores money as integers', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        WalletFacade::transaction($wallet)->credit(10.99)->execute();

        $wallet->refresh();
        expect($wallet->balance)->toBe(1099); // Stored as cents
    });

    test('handles fractional amounts correctly', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        WalletFacade::transaction($wallet)->credit(0.01)->execute();
        $balance = $this->manager->getBalance((int)$wallet->id);

        expect($balance->equals(Money::dollars(0.01)))->toBeTrue();
    });
});

describe('Metadata Handling', function () {
    test('stores and retrieves transaction metadata', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        $tx = WalletFacade::transaction($wallet)
            ->credit(100)
            ->description('Subscription payment')
            ->processor('stripe', 'ch_abc123')
            ->meta([
                'subscription_id' => 12345,
                'plan' => 'premium',
            ])
            ->execute();

        expect($tx->description)->toBe('Subscription payment')
            ->and($tx->payment_processor)->toBe('stripe')
            ->and($tx->processor_transaction_id)->toBe('ch_abc123');

        // Metadata is already decoded by model casts
        expect($tx->metadata['subscription_id'])->toBe(12345)
            ->and($tx->metadata['plan'])->toBe('premium');
    });
});

describe('Transaction Queries', function () {
    test('can filter transactions by type', function () {
        $transactionManager = resolve(TransactionManagerService::class);
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        WalletFacade::transaction($wallet)->credit(100)->execute();
        WalletFacade::transaction($wallet)->credit(50)->execute();
        WalletFacade::transaction($wallet)->debit(30)->execute();

        $transactions = WalletFacade::transactions($wallet)->credit()->get();

        expect($transactions)->toHaveCount(2);
    });

    test('can limit transaction results', function () {
        $transactionManager = resolve(TransactionManagerService::class);
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        for ($i = 0; $i < 10; $i++) {
            WalletFacade::transaction($wallet)->credit(10)->execute();
        }

        $transactions = WalletFacade::transactions($wallet)->limit(5)->get();

        expect($transactions)->toHaveCount(5);
    });
});

describe('Multi-Currency Support', function () {
    test('can create wallets in different currencies', function () {
        $walletUsd = WalletFacade::createWallet()->owner(1, 'User')->currency(Currency::USD)->create();
        $walletEur = WalletFacade::createWallet()->owner(1, 'User')->currency(Currency::EUR)->create();
        $walletGbp = WalletFacade::createWallet()->owner(1, 'User')->currency(Currency::GBP)->create();

        expect($walletUsd->currency)->toBe(Currency::USD)
            ->and($walletEur->currency)->toBe(Currency::EUR)
            ->and($walletGbp->currency)->toBe(Currency::GBP);
    });

    test('credits work with different currencies', function () {
        $walletEur = WalletFacade::createWallet()->owner(1, 'User')->currency(Currency::EUR)->create();
        $tx = WalletFacade::transaction($walletEur)->credit(50)->execute();

        expect($this->manager->toMoney($tx)->equals(Money::euros(50)))->toBeTrue();

        $walletEur->refresh();
        expect($walletEur->balance)->toBe(5000);
    });
});

describe('Reference ID Management', function () {
    test('auto-generates unique reference IDs', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        $tx1 = WalletFacade::transaction($wallet)->credit(100)->execute();
        $tx2 = WalletFacade::transaction($wallet)->credit(50)->execute();

        expect($tx1->reference_id)->not->toBe($tx2->reference_id);
    });

    test('allows custom reference IDs', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        $tx = WalletFacade::transaction($wallet)
            ->credit(100)
            ->reference('CUSTOM_REF_123')
            ->execute();

        expect($tx->reference_id)->toBe('CUSTOM_REF_123');
    });
});

describe('Transaction Status', function () {
    test('transactions default to COMPLETED status', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        $tx = WalletFacade::transaction($wallet)->credit(100)->execute();

        expect($tx->status)->toBe(TransactionStatus::COMPLETED);
        expect($tx->completed_at)->not->toBeNull();
    });
});

describe('Balance Snapshots', function () {
    test('records balance before and after snapshots', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        WalletFacade::transaction($wallet)->credit(100)->execute();
        $tx = WalletFacade::transaction($wallet)->credit(50)->execute();

        expect($this->manager->toMoney($tx)->equals(Money::dollars(50)))->toBeTrue()
            ->and($tx->balance_before)->toBe(10000) // $100
            ->and($tx->balance_after)->toBe(15000); // $150
    });
});

describe('Security Validation', function () {
    test('throws exception for negative credit amount', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();

        expect(fn () => WalletFacade::transaction($wallet)->credit(-100)->execute())
            ->toThrow(InvalidAmountException::class);
    });

    test('throws exception for negative debit amount', function () {
        $wallet = WalletFacade::createWallet()->owner(1, 'User')->create();
        WalletFacade::transaction($wallet)->credit(1000)->execute();

        expect(fn () => WalletFacade::transaction($wallet)->debit(-100)->execute())
            ->toThrow(InvalidAmountException::class);
    });
});
