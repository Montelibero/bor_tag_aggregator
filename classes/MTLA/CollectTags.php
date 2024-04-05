<?php

namespace MTLA;

use Closure;
use RuntimeException;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Account\AccountBalanceResponse;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Responses\Account\AccountSignerResponse;
use Soneso\StellarSDK\StellarSDK;

class CollectTags
{
    private StellarSDK $Stellar;
    private Closure $logger;
    private bool $debug_mode = false;

    private array $sources = [];

    private array $accounts = [];
    private array $balance_tokens = [];

    public function __construct(StellarSDK $Stellar)
    {
        $this->Stellar = $Stellar;

        // TODO: fix it
        if (!isset($this->logger)) {
            $this->setDefaultLogger();
        }
    }

    public function addSource(string $code, string $issuer): void
    {
        if (!$this->validateStellarAccountIdFormat($issuer)) {
            throw new RuntimeException('The issuer not a valid stellar account: ' . $issuer);
        }

        $this->sources[$code . '-' . $issuer] = true;
    }

    /**
     * @return string[]
     */
    public function getSources(): array
    {
        return array_keys($this->sources);
    }

    public function addBalanceToken(string $token): void
    {
        [, $issuer] = explode('-', $token);
        if (!$this->validateStellarAccountIdFormat($issuer)) {
            throw new RuntimeException('The issuer not a valid stellar account: ' . $issuer);
        }

        $this->balance_tokens[$token] = true;
    }

    public function run(): array
    {
        if (!isset($this->logger)) {
            $this->setDefaultLogger();
        }

        if (!$this->sources) {
            throw new RuntimeException('Missing sources for accounts');
        }

        foreach (array_keys($this->sources) as $source) {
            [$code, $issuer] = explode('-', $source);
            $this->fetchDataFromAssetHolders($code, $issuer);
        }

        return $this->processData();
    }

    public function fetchDataFromAssetHolders(string $code, string $issuer): void
    {
        $accounts = $this->fetchAssetHolders($code, $issuer);

        foreach ($accounts as $AccountResponse) {
            if ($AccountResponse instanceof AccountResponse) {
                $this->processStellarAccount($AccountResponse);
            }
        }
    }

    /**
     * @param string $code
     * @param string $issuer
     * @return AccountResponse[]
     * @throws HorizonRequestException
     */
    public function fetchAssetHolders(string $code, string $issuer): array
    {
        $Asset = Asset::createNonNativeAsset($code, $issuer);

        $Accounts = $this->Stellar
            ->accounts()
            ->forAsset($Asset)
            ->limit(200)
            ->execute();
        $this->log('Fetch accounts page for ' . $code);
        $accounts = [];
        do {
            $this->log('Got new ' . $Accounts->getAccounts()->count() . ' accounts');
            foreach ($Accounts->getAccounts() as $Account) {
                $accounts[] = $Account;
            }
            $Accounts = $Accounts->getNextPage();
            $this->log('Fetch next accounts ' . $code);
        } while ($Accounts->getAccounts()->count());

        $this->log('Finally: ' . count($accounts) . ' accounts');
        return $accounts;
    }

    //region Logging
    public function setLogger(Closure $logger): void
    {
        $this->logger = $logger;
    }

    public function setDefaultLogger(): void
    {
        $this->setLogger(function (bool $debug, string $string) {
            if (!$debug || $this->debug_mode) {
                print $string . "\n";
            }
        });
    }

    public function isDebugMode(bool $debug_mode = null): bool
    {
        if ($debug_mode !== null) {
            $this->debug_mode = $debug_mode;
        }

        return $this->debug_mode;
    }

    public function log(string $string = ''): void
    {
        ($this->logger)(true, $string);
    }

    public function print(string $string = ''): void
    {
        ($this->logger)(false, $string);
    }

    //endregion

    private function validateStellarAccountIdFormat(?string $account_id): bool
    {
        if (!$account_id) {
            return false;
        }

        return preg_match('/\AG[A-Z2-7]{55}\Z/', $account_id);
    }

    private function processStellarAccount(AccountResponse $AccountResponse): void
    {
        $account_id = $AccountResponse->getAccountId();

        if (array_key_exists($account_id, $this->accounts)) {
            return;
        }

        $balances = $this->getBalances($AccountResponse);

        $tags = $this->getTags($AccountResponse);

        if ($signers = $this->getSigners($AccountResponse)) {
            $tags['Signer'] = $signers;
        }

        $this->accounts[$account_id] = [
            'balances' => $balances,
            'tags' => $tags,
        ];
    }

    private function getBalances(AccountResponse $AccountResponse): array
    {
        $balances = [];
        foreach ($AccountResponse->getBalances()->toArray() as $Asset) {
            if (($Asset instanceof AccountBalanceResponse)
            ) {
                if ($Asset->getAssetType() === Asset::TYPE_NATIVE) {
                    $balances['XLM'] = $Asset->getBalance();
                    continue;
                }

                if (array_key_exists($Asset->getAssetCode() . '-' . $Asset->getAssetIssuer(), $this->balance_tokens)) {
                    $balances[$Asset->getAssetCode()] = $Asset->getBalance();
                }
            }
        }

        return $balances;
    }

    private function getTags(AccountResponse $AccountResponse): array
    {
        $account_id = $AccountResponse->getAccountId();

        $tags = [];
        $Data = $AccountResponse->getData();
        foreach ($Data->getKeys() as $key) {
            $value = $Data->get($key);
            if (!$this->validateStellarAccountIdFormat($value)) {
                continue;
            }
            if ($value === $account_id) {
                continue;
            }

            $key = preg_replace('/\s?\d+\Z/', '', $key);

            if ($key === 'Signer') {
                continue;
            }

            if (!array_key_exists($key, $tags)) {
                $tags[$key] = [];
            }
            $tags[$key][] = $value;
        }

        return $tags;
    }

    private function getSigners(AccountResponse $AccountResponse): array
    {
        $co_signers = [];
        $Signers = $AccountResponse->getSigners();
        /** @var AccountSignerResponse $Signer */
        foreach ($Signers->toArray() as $Signer) {
            $key = $Signer->getKey();
            if ($key === $AccountResponse->getAccountId()) {
                continue;
            }
            if (!$this->validateStellarAccountIdFormat($key)) {
                continue;
            }
            $co_signers[] = $key;
        }

        return $co_signers;
    }

    private function processData(): array
    {
        foreach ($this->accounts as & $data) {
            if ($data['tags']) {
                $data['has_tag_out'] = true;
            }

            foreach ($data['tags'] as $items) {
                foreach ($items as $item) {
                    if (array_key_exists($item, $this->accounts)) {
                        $this->accounts[$item]['has_tag_in'] = true;
                    }
                }
            }
        }
        unset($data);

        $result = [];
        foreach ($this->accounts as $id => $data) {
            if (!array_key_exists('has_tag_out', $data) && !array_key_exists('has_tag_in', $data)) {
                continue;
            }

            $result[$id] = [
                'tags' => $data['tags'],
                'balances' => $data['balances'],
            ];
        }

        return $result;
    }
}
