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

// JSON

$fp = gzopen('bor-new.json.gz', 'w9');
gzwrite($fp, json_encode($result, JSON_UNESCAPED_UNICODE));
gzclose($fp);

rename('bor-new.json.gz', 'bor.json.gz');

// HTML

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

$Twig = new Environment(new FilesystemLoader(__DIR__ . '/templates'), [
//    'cache' => 'twig_cache',
]);
$Twig->addExtension(new \MTLA\TwigAppExtension($result));
$Template = $Twig->load('simple_html.twig');
$fp = gzopen('bor-new.html.gz', 'w9');
gzwrite($fp, $Template->render($result));
gzclose($fp);
rename('bor-new.html.gz', 'bor.html.gz');
