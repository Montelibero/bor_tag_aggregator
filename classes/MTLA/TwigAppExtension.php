<?php

namespace MTLA;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigAppExtension extends AbstractExtension
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('short_account', [$this, 'shortAccount']),
            new TwigFilter('html_account', [$this, 'htmlAccount'], [
                'is_safe' => [
                    'html'
                ]
            ]),
        ];
    }

    public function shortAccount($account)
    {
        return substr($account, 0, 4) . '…' . substr($account, -4);
    }

    public function htmlAccount($account)
    {
        $short_name = $this->shortAccount($account);

        $link = "https://stellar.expert/explorer/public/account/";
        $mtla_accounts = [
            'GCNVDZIHGX473FEI7IXCUAEXUJ4BGCKEMHF36VYP5EMS7PX2QBLAMTLA',
            'GDGC46H4MQKRW3TZTNCWUU6R2C7IPXGN7HQLZBJTNQO6TW7ZOS6MSECR',
        ];

        $stars = '';
        if (array_key_exists($account, $this->data['accounts']) && array_key_exists('balances', $this->data['accounts'][$account])) {
            $balances = $this->data['accounts'][$account]['balances'];
            if (in_array($account, $mtla_accounts, true)) {
                $stars = ' ' . str_repeat('🌟', 5);
            } else if (array_key_exists('MTLAP', $balances) && $balances['MTLAP']) {
                $stars = ' ' . str_repeat('⭐', (int)$balances['MTLAP']);
            } else if (array_key_exists('MTLAC', $balances) && $balances['MTLAC']) {
                $stars = ' ' . str_repeat('🌟', (int)$balances['MTLAC']);
            }
        }
        $name = '';
        if ($stars
            && array_key_exists($account, $this->data['accounts'])
            && array_key_exists('profile', $this->data['accounts'][$account])
            && array_key_exists('Name', $this->data['accounts'][$account]['profile'])
        ) {
            $name = ' [' . htmlspecialchars($this->data['accounts'][$account]['profile']['Name'][0]) . ']';
        }

        return "<span class='acc'><a href='#account_$account'>#</a> <a href=\"" . htmlspecialchars($link . $account) . "\">" . $short_name . $name . $stars . "</a></span>";
    }

}