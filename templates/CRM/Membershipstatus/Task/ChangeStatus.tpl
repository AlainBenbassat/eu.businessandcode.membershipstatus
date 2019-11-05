<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
  {include file="CRM/Member/Form/Task.tpl"}
</div>
<div id="help">
  {$detailedInfo}
</div>

<div class="spacer"></div>

{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}

<div class="spacer"></div>
<div class="form-item">
  {$form.buttons.html}
</div>

