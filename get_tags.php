<?php

use Soneso\StellarSDK\StellarSDK;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require 'vendor/autoload.php';

$CollectTags = new MTLA\CollectTags(
    StellarSDK::getPublicNetInstance()
);

$CollectTags->isDebugMode(false);

$tokens = [
    'Agora-GBGGX7QD3JCPFKOJTLBRAFU3SIME3WSNDXETWI63EDCORLBB6HIP2CRR',
    'BTCMTL-GACKTN5DAZGWXRWB2WLM6OPBDHAMT6SJNGLJZPQMEZBUR4JUGBX2UK7V',
    'EURMTL-GACKTN5DAZGWXRWB2WLM6OPBDHAMT6SJNGLJZPQMEZBUR4JUGBX2UK7V',
    'EURTPS-GDEF73CXYOZXQ6XLUN55UBCW5YTIU4KVZEPOI6WJSREN3DMOBLVLZTOP',
    'GPA-GBGGX7QD3JCPFKOJTLBRAFU3SIME3WSNDXETWI63EDCORLBB6HIP2CRR',
    'MTL-GACKTN5DAZGWXRWB2WLM6OPBDHAMT6SJNGLJZPQMEZBUR4JUGBX2UK7V',
    'MTLAC-GCNVDZIHGX473FEI7IXCUAEXUJ4BGCKEMHF36VYP5EMS7PX2QBLAMTLA',
    'MTLAP-GCNVDZIHGX473FEI7IXCUAEXUJ4BGCKEMHF36VYP5EMS7PX2QBLAMTLA',
    'MTLRECT-GACKTN5DAZGWXRWB2WLM6OPBDHAMT6SJNGLJZPQMEZBUR4JUGBX2UK7V',
    'SATSMTL-GACKTN5DAZGWXRWB2WLM6OPBDHAMT6SJNGLJZPQMEZBUR4JUGBX2UK7V',
    'TIC-GBJ3HT6EDPWOUS3CUSIJW5A4M7ASIKNW4WFTLG76AAT5IE6VGVN47TIC',
    'TOC-GBJ3HT6EDPWOUS3CUSIJW5A4M7ASIKNW4WFTLG76AAT5IE6VGVN47TIC',
    'TPS-GAODFS2M4NSBFGKVNG6SEECI3DWU2GXQKG6MUBYJEIIINVIPZULCJTPS',
    'USDC-GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    'USDM-GDHDC4GBNPMENZAOBB4NCQ25TGZPDRK6ZGWUGSI22TVFATOLRPSUUSDM',
];
foreach ($tokens as $token) {
    $CollectTags->addBalanceToken($token);
}

$CollectTags->addSource('MTLAP', 'GCNVDZIHGX473FEI7IXCUAEXUJ4BGCKEMHF36VYP5EMS7PX2QBLAMTLA');
$CollectTags->addSource('MTLAC', 'GCNVDZIHGX473FEI7IXCUAEXUJ4BGCKEMHF36VYP5EMS7PX2QBLAMTLA');
$CollectTags->addSource('EURMTL', 'GACKTN5DAZGWXRWB2WLM6OPBDHAMT6SJNGLJZPQMEZBUR4JUGBX2UK7V');

$data = $CollectTags->run();

$result = [
    'createDate' => (new DateTime('now', new DateTimeZone('UTC')))->format('c'),
    'knownTokens' => $tokens,
    'usedSources' => $CollectTags->getSources(),
    'accounts' => $data,
];

// JSON, только базовые данные, для всех
file_put_contents('bsn-new.json', json_encode($result, JSON_UNESCAPED_UNICODE));
rename('bsn-new.json', 'bsn.json');
file_put_contents('bsn-new.json,gz', gzencode(json_encode($result, JSON_UNESCAPED_UNICODE), 9));
rename('bsn-new.json,gz', 'bsn.json.gz');

// Тут уже красота и отсебятина
// HTML

// Входящие теги
foreach ($result['accounts'] as $account => $datum) {
    if (!array_key_exists('tags', $datum)) {
        continue;
    }
    foreach ($datum['tags'] as $tag => $accounts) {
        foreach ($accounts as $acc) {
            if (!array_key_exists($acc, $result['accounts'])) {
                $result['accounts'][$acc] = [];
            }
            if (!array_key_exists('income', $result['accounts'][$acc])) {
                $result['accounts'][$acc]['income'] = [];
            }
            if (!array_key_exists($tag, $result['accounts'][$acc]['income'])) {
                $result['accounts'][$acc]['income'][$tag] = [];
            }
            $result['accounts'][$acc]['income'][$tag][] = $account;
        }
    }
}
// Отсортировать входящие теги так же, как сортированы исходящие
foreach ($result['accounts'] as $account => & $datum) {
    if (array_key_exists('income', $datum)) {
        $CollectTags->semantic_sort_keys($datum['income'], $CollectTags->sort_tags_example);
    }
}
// Level
$mtla_accounts = [
    'GCNVDZIHGX473FEI7IXCUAEXUJ4BGCKEMHF36VYP5EMS7PX2QBLAMTLA',
    'GDGC46H4MQKRW3TZTNCWUU6R2C7IPXGN7HQLZBJTNQO6TW7ZOS6MSECR',
];
foreach ($result['accounts']  as $account => & $datum) {
    if (in_array($account, $mtla_accounts, true)) {
        $datum['relation'] = [
            'type' => 'mtlap',
            'level' => 5,
        ];
        continue;
    }

    if (!array_key_exists('balances', $datum)) {
        continue;
    }

    if (array_key_exists('MTLAP', $datum['balances']) && (int) $datum['balances']['MTLAP']) {
        $datum['relation'] = [
            'type' => 'mtlap',
            'level' => intval($datum['balances']['MTLAP']),
        ];
    } else if (array_key_exists('MTLAC', $datum['balances']) && (int) $datum['balances']['MTLAC']) {
        $datum['relation'] = [
            'type' => 'mtlac',
            'level' => intval($datum['balances']['MTLAC']),
        ];
    }
}
// Inherited level
foreach ($result['accounts']  as $account => & $datum) {
    if (
        array_key_exists('relation', $datum)
        || !array_key_exists('tags', $datum)
        || !array_key_exists('Owner', $datum['tags'])
        || count($datum['tags']['Owner']) !== 1
    ) {
        continue;
    }

    $owner_id = $datum['tags']['Owner'][0];

    if (
        !array_key_exists($owner_id, $result['accounts'])
        || !array_key_exists('relation', $result['accounts'][$owner_id])
        || !array_key_exists('tags', $result['accounts'][$owner_id])
        || !array_key_exists('OwnershipFull', $result['accounts'][$owner_id]['tags'])
        || !in_array($account, $result['accounts'][$owner_id]['tags']['OwnershipFull'], true)

    ) {
        continue;
    }

    $datum['relation'] = $result['accounts'][$owner_id]['relation'];
    $datum['relation']['inherited'] = true;
}
// Second level
$good_tags = $CollectTags->sort_tags_example;
foreach ($result['accounts']  as $account => & $datum) {
    if (
        array_key_exists('relation', $datum)
        || !array_key_exists('income', $datum)
    ) {
        continue;
    }

    foreach ($datum['income'] as $tag_name => $taggers) {
        if (!in_array($tag_name, $good_tags, true)) {
            continue;
        }

        foreach ($taggers as $tagger) {
            if (
                array_key_exists('relation', $result['accounts'][$tagger])
                && $result['accounts'][$tagger]['relation']['type'] !== 'second'
            ) {
                $datum['relation'] = [
                    'type' => 'second',
                ];
                break 2;
            }
        }
    }

}

file_put_contents(
    'bsn-extra-new.json,gz',
    gzencode(json_encode($result, JSON_UNESCAPED_UNICODE), 9)
);
rename('bsn-extra-new.json,gz', 'bsn-extra.json,gz');

$Twig = new Environment(new FilesystemLoader(__DIR__ . '/templates'), [
//    'cache' => 'twig_cache',
]);
$Twig->addExtension(new \MTLA\TwigAppExtension($result));
$Template = $Twig->load('simple_html.twig');
$fp = gzopen('bsn-new.html.gz', 'w9');
gzwrite($fp, $Template->render($result));
gzclose($fp);
rename('bsn-new.html.gz', 'bsn.html.gz');
