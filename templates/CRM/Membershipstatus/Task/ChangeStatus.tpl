<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
  {include file="CRM/Member/Form/Task.tpl"}
</div>
<div id="help">
  {$detailedInfo}
</div>

<div class="spacer"></div>

<span>{$form.status_id.label}: </span>
{$form.status_id.html}

<div class="spacer"></div>
<div class="form-item">
  {$form.buttons.html}
</div>

