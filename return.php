<?php

use WHMCS\Module\Addon\Realname\ClientModel;
use WHMCS\Module\Addon\Realname\PendingModel;

// show error
ini_set('display_errors', 1);

if (!isset($_REQUEST['data'])) {
    return false;
}

$data = json_decode($_REQUEST['data'], true);

$verify = verifyIfSuccess($_REQUEST['data'], $_REQUEST['sign']);

if (!$verify) {
    echo '签名验证失败。';
    exit;
}

if ($data['code'] !== 'PASS') {
    echo '返回代码验证不通过。';
}


$key = $data['bizNo'];


// 这里再引入，否则 WHMCS 会处理 REQUEST 的数据，会签名验证失败。
require_once '../../../init.php';

$pending = PendingModel::where('key', $key)->first();

if (!$pending) {
    echo '找不到对应的待审核记录。';
} else {
    // 保存到数据库
    ClientModel::create([
        'client_id' => $pending->client_id,
        'name' => $pending->name,
        'id_card' => $pending->id_card,
        'verified_at' => date('Y-m-d H:i:s'),
    ]);

    // 删除待审核记录
    $pending->delete();

    // 删除该用户的所有待审核记录
    $pending = PendingModel::where('client_id', $pending->client_id)->first();
}


exit(<<<EOF
<script>
window.location.href = '/index.php?m=Realname';
</script>
EOF);


function verifyIfSuccess(string $request, string $sign): bool
{
    $public_key = <<<EOF
-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEWKKJoLwh6XEBkTeCfVbKSB3zkkycbIdd8SBabj2jpWynXx0pBZvdFpbb9AEiyrnM8bImhpz8YOXc2yUuN1ui/w==
-----END PUBLIC KEY-----
EOF;

    $sign = base64_decode($sign);

    $public_key = openssl_pkey_get_public($public_key);

    if (!$public_key) {
        exit('公钥不可用。');
    }

    $flag = openssl_verify($request, $sign, $public_key, OPENSSL_ALGO_SHA256);

    return $flag === 1;
}
