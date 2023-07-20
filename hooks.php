<?php

use WHMCS\Module\Addon\Realname\ClientModel;
use WHMCS\Module\Addon\Realname\ProductModel;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

add_hook('AdminAreaClientSummaryPage', 1, function ($vars) {
    $html = '';

    if ($_GET['realname_action'] == 'clear') {
        $client = ClientModel::where('client_id', $vars['userid'])->first();

        if ($client) {
            $client->delete();
        }

        return <<<EOF
<span class="label label-success">实名认证信息已清除。</span>
<script>
// 清除 URL 中的 &realname_action=clear
history.replaceState(null, null, location.href.replace('&realname_action=clear', ''));  
</script>
EOF;
        
    }

    $info = ClientModel::where('client_id', $vars['userid'])->first();


    if ($info) {

        $html = '<span class="label label-success">已通过实名认证，<span id="realname_info">显示详细信息。</span><span id="realname_action"></span></span>';

        $name = $info->name;
        $id_card = $info->id_card;

        // 点击显示和隐藏
        $html .= '<script>
        $(function(){
            let realname_info = "' . $name . ' ' . $id_card . '。" + "<a href=\"clientssummary.php?userid=' . $vars['userid'] . '&realname_action=clear\" class=\"text-danger\">清除实名信息</a>";

            $("#realname_info").click(function(){
                 $("#realname_info").html(realname_info);
            });
        });
        </script>';
    } else {
        $html = '<span class="label label-danger">未通过实名认证</span>';
    }

    return $html;
});

// add_hook('PreCalculateCartTotals', 1, function ($vars) {
//     // 检测用户是否已经登录
//     if (!isset($_SESSION['uid'])) {
//         return;
//     }

//     $products = $vars['products'];
//     $pids = [];
//     foreach ($products as $key => $value) {
//         $pids[] = $value['pid'];
//     }

//     $pids = array_unique($pids);

//     $product = ProductModel::whereIn('product_id', $pids)->first();

//     // 检测用户是否已经实名认证
//     $verifyinfo = ClientModel::where('client_id', $_SESSION['uid'])->first();
//     if (!$verifyinfo && $product) {
//         echo '<div class="alert alert-danger" role="alert">您选择的产品需要实名认证。请先实名认证才能购买产品。</div>';
//     }
// });


add_hook('PreShoppingCartCheckout', 1, function ($vars) {
    // 检测用户是否已经登录
    if (!isset($_SESSION['uid'])) {
        return;
    }

    // 检测是否是在 admin
    if (isset($_SESSION['adminid'])) {
        return;
    }

    $products = $vars['products'];
    $pids = [];
    foreach ($products as $key => $value) {
        $pids[] = $value['pid'];
    }

    $pids = array_unique($pids);

    $product = ProductModel::whereIn('product_id', $pids)->first();

    // 检测用户是否已经实名认证
    $verifyinfo = ClientModel::where('client_id', $_SESSION['uid'])->first();
    if (!$verifyinfo && $product) {
        // 跳转到实名认证页面
        header("Location: index.php?m=Realname");
        exit;
    }

});