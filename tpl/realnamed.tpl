{* 已经实名认证 *}
<div class="alert alert-success">
    <i class="fa fa-check-circle"></i> 您已经通过实名认证！
</div>

{* 显示实名认证信息 *}
<div class="mb-3">
    <label for="real_name" class="form-label">姓名</label>
    <input required type="text" class="form-control" readonly value="{$name}">
</div>
<div class="mb-3">
    <label for="id_card" class="form-label">身份证号</label>
    <input required type="text" class="form-control" readonly value="{$id_card}">
</div>

<p>如果您需要修改实名认证信息，请提交工单。</p>