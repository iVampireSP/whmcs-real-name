<?php

use WHMCS\Product\Product;
use WHMCS\Database\Capsule;
use WHMCS\Authentication\CurrentUser;
use WHMCS\Module\Addon\Realname\ClientModel;
use WHMCS\Module\Addon\Realname\PendingModel;
use WHMCS\Module\Addon\Realname\ProductModel;

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

function Realname_config()
{
    $config = [
        "name" => "实名认证",
        "description" => "用户需要实名认证才可以购买相应的产品。",
        "version" => "1.0",
        "author" => "LaeCloud.com & iVampireSP.com",
        "fields" => [
            "realname_app_code" => [
                "FriendlyName" => "实名认证 AppCode",
                "Type" => "text",
                "Size" => 50,
            ]
        ]
    ];

    return $config;
}

function Realname_activate()
{
    // Create custom tables and schema required by your module
    try {
        Capsule::schema()
            ->create(
                'mod_realname_clients',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->id();
                    $table->unsignedBigInteger('client_id')->index();
                    $table->string('name')->nullable()->index();
                    $table->string('id_card')->nullable()->index();
                    $table->dateTime('verified_at')->nullable()->index();
                    $table->timestamps();
                }
            );

        Capsule::schema()
            ->create(
                'mod_realname_pending',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->id();
                    $table->unsignedBigInteger('client_id')->index();
                    $table->string('name');
                    $table->string('id_card');
                    $table->string('key')->index();
                    $table->timestamps();
                }
            );


        Capsule::schema()
            ->create(
                'mod_realname_products',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->id();
                    $table->unsignedBigInteger('product_id')->index();
                    $table->timestamps();
                }
            );

        return [
            'status' => 'success',
            'description' => '实名认证系统模块激活成功. 点击 配置 对模块进行设置。',
        ];
    } catch (\Exception $e) {
        return [
            'status' => "error",
            'description' => '无法创建表: ' . $e->getMessage(),
        ];
    }
}

function Realname_output($vars)
{

    if ($_POST['action'] == 'update') {
        $product_id = $_POST['product_id'];
        $need_realname = $_POST['need_realname'] ? 1 : 0;

        // if ProductModel has product_id
        $product = ProductModel::where('product_id', $product_id)->first();

        if ($need_realname) {
            if (!$product) {
                ProductModel::create([
                    'product_id' => $product_id,
                ]);
            }
        } else {
            if ($product) {
                $product->delete();
            }
        }

        exit(json_encode([
            'status' => 'success',
            'message' => '更新成功',
        ]));
    } else if ($_POST['action'] == 'delete') {
        $client_id = $_POST['client_id'];

        $client = ClientModel::where('client_id', $client_id)->first();

        if ($client) {
            $client->delete();
        }

        exit(json_encode([
            'status' => 'success',
            'message' => '删除成功',
        ]));
    }

    $products_real_name = ProductModel::all()->pluck('product_id')->toArray();
    $products = Product::with('ProductGroup')->get()->toArray();

    // header('Content-Type: application/json');
    // exit(json_encode($products));
    // exit;

    echo <<<EOF
<table class="datatable no-margin" width="100%" border="0" cellspacing="1" cellpadding="3">
    <thead>
        <tr>
            <th style="width: 90%">产品名称</th>
            <th>实名认证</th>
        </tr>
    </thead>
    <tbody>
EOF;

    foreach ($products as $product) {
        $checked = in_array($product['id'], $products_real_name) ? 'checked' : '';
        echo <<<EOF
        <tr>
            <td>{$product['product_group']['name']} - {$product['name']}</td>
            <td style="text-align:center">
                <input type="checkbox" class="realname" data-product-id="{$product['id']}" {$checked}>
                <span class="realname-status" data-product-id="{$product['id']}"></span>
            </td>
        </tr>
EOF;
    }

    echo <<<EOF
    </tbody>
</table>
EOF;

    echo <<<EOF
<script>
    $(function() {
        $('.realname').on('click', function() {
            let product_id = $(this).data('product-id');
            let need_realname = $(this).prop('checked') ? 1 : 0;

            let realname_status = $('.realname-status[data-product-id=' + product_id + ']');
            // realname_status.html('<i class="fas fa-spinner fa-spin"></i>');

            $(this).prop('disabled', true);

            $.post('addonmodules.php?module=Realname', {
                action: 'update',
                product_id: product_id,
                need_realname: need_realname,
            }, function(data) {
                if (data.status == 'success') {
                    // realname_status.html('<i class="fas fa-check-circle" style="color: green"></i>');
                } else {
                    realname_status.html('<i class="fas fa-times-circle" style="color: red"></i>');
                }

                $('.realname').prop('disabled', false);

            }, 'json');
        });
    });
</script>

EOF;
}

function Realname_clientarea($vars)
{
    // 获取 systemurl
    $systemurl = $GLOBALS['CONFIG']['SystemURL'];

    $return_url = $systemurl . '/modules/addons/Realname/return.php';
    $error = '';
    $success = '';


    $realname_app_code = $vars['realname_app_code'];


    $currentUser = new CurrentUser();
    $selectedClient = $currentUser->client();

    if (!is_null($selectedClient)) {
        $client_id = $selectedClient->id;
    }

    // $modulelink = $vars['modulelink'];

    if ($_GET['action'] == 'relogin_loliart') {
        $_SESSION['redirect_uri'] = '/index.php?m=Realname';
        header('Location: /oauth/redirect.php');
        exit;
    }

    if ($_POST['action'] == 'realname') {
        if (!is_null($selectedClient)) {
            $error = '请切换到正确的用户。';
        }

        $name = $_POST['real_name'];
        $id_card = $_POST['id_card'];

        // 检测是否被认证过
        $exists = ClientModel::where('id_card', $id_card)->exists();

        if ($exists) {
            $error = '该身份证已被认证过。';
        } else {
            // 检测年龄是否在 16 周岁以上, 60 周岁以下
            try {
                $age = Realname_getAgeFromIdCard($id_card);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }

            if ($age < 16 || $age > 60) {
                $error = '年龄不符合要求。';
            }

            $result = Realname_start_verify($client_id, $name, $id_card, $realname_app_code, $return_url);

            if (isset($result['url'])) {
                header('Location: ' . $result['url']);
                exit;
            } else {
                $error = $result['error'];
            }
        }

    }

    $realname = ClientModel::where('client_id', $client_id)->first();

    $tpl = 'tpl/clientarea';

    if ($realname) {
        $tpl = 'tpl/realnamed';
    }

    return [
        'pagetitle' => '实名认证',
        'breadcrumb' => [
            'index.php?m=Realname' => '实名认证'
        ],
        'templatefile' => $tpl,
        'requirelogin' => true,
        'forcessl' => true,
        'vars' => array(
            'realnamed' => $realname,
            'name' => $realname->name ?? '',
            'id_card' => $realname->id_card ?? '',
            'error' => $error,
            'success' => $success,
        ),
    ];

}

function Realname_getAgeFromIdCard($idCard)
{
    // 正则表达式匹配身份证号码格式
    $pattern = '/^\d{17}[\dXx]$/';

    // 检查身份证格式是否正确
    if (!preg_match($pattern, $idCard)) {
        throw new Exception('身份证格式不正确');
    }

    #  获得出生年月日的时间戳
    $date = strtotime(substr($idCard, 6, 8));
    #  获得今日的时间戳
    $today = strtotime('today');
    #  得到两个日期相差的大体年数
    $diff = floor(($today - $date) / 86400 / 365);
    #  strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
    $age = strtotime(substr($idCard, 6, 8) . ' +' . $diff . 'years') > $today ? ($diff + 1) : $diff;

    return $age;
}

function Realname_start_verify($client_id, $name, $id_card, $realname_app_code, $return)
{
    $url = 'https://faceidh5.market.alicloudapi.com';

    $key = Realname_random_str();

    // 检测这个客户的实名认证是否已经存在
    $realname = ClientModel::where('client_id', $client_id)->first();

    if ($realname) {
        return [
            'error' => '您已经实名认证过了。',
        ];
    }

    // 创建请求
    PendingModel::updateOrCreate([
        'client_id' => $client_id,
        'name' => $name,
        'id_card' => $id_card,
        'key' => $key,
    ]);

    // 转为 Guzzle
    $client = new GuzzleHttp\Client();

    $data = [
        'bizNo' => $key,
        'idNumber' => $id_card,
        'idName' => $name,
        'pageTitle' => '实名认证',
        'notifyUrl' => $return,
        'procedureType' => 'video',
        'txtBgColor' => '#cccccc',

        'ocrIncIdBack' => 'false',
        'ocrOnly' => 'false',
        'pageBgColor' => 'false',
        'retIdImg' => 'false',
        'returnImg' => 'false',
        'returnUrl' => $return,
    ];

    $resp = $client->request('POST', $url . '/edis_ctid_id_name_video_ocr_h5', [
        'headers' => [
            'Authorization' => 'APPCODE ' . $realname_app_code,
            'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
            'Accept' => 'application/json',
        ],
        'form_params' => $data,

    ])->getBody()->getContents();

    $resp = json_decode($resp, true);

    if (!$resp || $resp['code'] !== '0000') {
        return [
            'error' => '调用远程服务器时出现了问题，请检查身份证号码是否正确。',
        ];
    }

    return [
        'url' => $resp['verifyUrl']
    ];

}


function Realname_random_str(int $length = 32)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}