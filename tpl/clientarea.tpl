{* 需要实名认证 *}
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> 您需要先进行实名认证，才能购买相应产品，或执行相应操作。
</div>

{* 如果有 error *}
{if $error}
    <div class="alert alert-danger">
        <i class="fa fa-times"></i> {$error}
    </div>
{/if}


<form action="?m=Realname" method="post">
    <input type="hidden" name="action" value="realname">
    <div class="mb-3">
        <label for="real_name" class="form-label">姓名</label>
        <input required type="text" class="form-control" id="real_name" name="real_name" placeholder="请输入您的姓名"
            autocomplete="off" maxlength="6">
    </div>
    <div class="mb-3">
        <label for="id_card" class="form-label">身份证号</label>
        <input required type="text" class="form-control" id="id_card" name="id_card" placeholder="请输入您的身份证号"
            autocomplete="off" maxlength="18">
    </div>
    <button type="submit" class="btn btn-primary">提交</button>
    <a href="index.php?m=Realname&action=relogin_loliart" class="btn btn-secondary">从 LoliArt Account 同步</a>
</form>
<br />
<p>您需要使用移动设备来人脸认证。</p>
<p>实名认证信息和 LoliArt Account 互通。如果您有 LoliArt Account 账号，可退出后使用 LoliArt Account 登录，即可从 LoliArt Account 同步实名认证信息。</p>