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

        $relation = [
            'type' => 'noname',
        ];
        if (array_key_exists($account, $this->data['accounts'])
            && array_key_exists('relation', $this->data['accounts'][$account])
        ) {
            $relation = $this->data['accounts'][$account]['relation'];
        }

        $name = '';
        if (
            $relation['type'] !== 'noname'
            && array_key_exists($account, $this->data['accounts'])
            && array_key_exists('profile', $this->data['accounts'][$account])
            && array_key_exists('Name', $this->data['accounts'][$account]['profile'])
        ) {
            $name = ' [' . htmlspecialchars($this->data['accounts'][$account]['profile']['Name'][0]) . ']';
        }

        if ($relation['type'] === 'mtlap') {
            $icons = ' ' . str_repeat('⭐', $relation['level']);
        } else if ($relation['type'] === 'mtlac') {
            $icons = ' ' . str_repeat('🌟', $relation['level']);
        } else if ($relation['type'] === 'second') {
            $icons = ' 🔗';
        } else {
            $icons = ' 👻';
        }

        return "<span class='acc'><a href='#account_$account'>#</a> <a href=\"" . htmlspecialchars($link . $account) . "\">" . $short_name . $name . $icons . "</a></span>";
    }

}