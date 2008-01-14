{def $eventData = $event.data }

<div class="block">

{* Sections *}
<div class="element">
    <label>{'Affected sections'|i18n( 'ezapprove2' )}:</label>
    <select name="WorkflowEvent_event_ezapprove_section_{$event.id}[]" size="5" multiple="multiple">
    <option value="-1" {cond( $eventData.selected_section_list|contains( -1 ), 'selected="selected"', '' )}>{'All sections'|i18n( 'ezapprove2' )}</option>
    {foreach $event.workflow_type.sections as $section}
        <option value="{$section.value}" {cond( $eventData.selected_section_list|contains( $section.value ), 'selected="selected"', '')}>{$section.name|wash}</option>
    {/foreach}
    </select>
</div>

<div class="block">

<fieldset>
<legend><label><input type="radio" name="ApproveType_{$event.id}" value="1" {cond( $eventData.approve_type|eq(1), 'checked="checked"', '' )} />{'Users select approvers themselves'|i18n( 'ezapprove2' )}</label></legend>
{'Users select who should approve the content when publishing'|i18n('ezapprove2')}
<div class="block">
    <div class="element">
        {'Required number of users to approve content'|i18n( 'ezapprove2' )}
    </div>
    <div class="element">
        <select name="RequiredNumberApproves_{$event.id}">
            <option value="-1" {cond( $eventData.num_approve_users|eq(-1), 'selected="selected"', '')}>{'Any'|i18n( 'ezapprove2' )}</option>
            {for 1 to 10 as $num}
                <option value="{$num}" {cond( $eventData.num_approve_users|eq($num), 'selected="selected"', '')}>{$num}</option>
            {/for}
        </select>
    </div>
</div>
<div class="block">
    <fieldset>
        <legend>{'Select if one or all is enough to approve content.'|i18n( 'ezapprove2' )}</legend>
        {foreach $eventData.require_approve_name_map as $value => $text}
            <label><input type="radio" name="ApproveOneAll_{$event.id}" value="{$value}" {cond( $eventData.require_all_approve|eq($value), 'checked="checked"', '' )} />{$text|wash}</label>
        {/foreach}
    </fieldset>
    <fieldset>
        <legend>{'Allow approvers to be added while approving content.'|i18n( 'ezapprove2' )}</legend>
        {foreach $eventData.add_approver_name_map as $value => $text}
            <label><input type="radio" name="ApproveAllowAddApprover_{$event.id}" value="{$value}" {cond( $eventData.allow_add_approver|eq($value), 'checked="checked"', '' )} />{$text|wash}</label>
        {/foreach}
    </fieldset>
</div>
</fieldset>

<fieldset>
<legend><label><input type="radio" name="ApproveType_{$event.id}" value="0" {cond( $eventData.approve_type|eq(0), 'checked="checked"', '' )} />{'Pre selected approvers'|i18n( 'ezapprove2' )}</label></legend>
{* User who functions as approver *}
<fieldset>
<legend>{'Users who approves content'|i18n( 'ezapprove2' )}</legend>
{if $eventData.approve_user_list}
    <table class="list" cellspacing="0">
    <tr>
        <th class="tight">&nbsp;</th>
        <th>{'User'|i18n( 'ezapprove2' )}</th>
    </tr>
    {foreach $eventData.approve_user_list as $userID
             sequence array( bglight, bgdark ) as $sequence}
        <tr class="{$sequence}">
            <td><input type="checkbox" name="DeleteApproveUserIDArray_{$event.id}[]" value="{$userID}" />
            <input type="hidden" name="WorkflowEvent_event_user_id_{$event.id}[]" value="{$userID}" /></td>
            <td>{fetch(content, object, hash( object_id, $userID)).name|wash}</td>
        </tr>
    {/foreach}
    </table>
{else}
    <p>{'No users selected.'|i18n( 'ezapprove2' )}</p>
{/if}

<input class="button" type="submit" name="CustomActionButton[{$event.id}_RemoveApproveUsers]" value="{'Remove selected'|i18n( 'ezapprove2' )}"
       {section show=$eventData.approve_user_list|not}disabled="disabled"{/section} />
<input class="button" type="submit" name="CustomActionButton[{$event.id}_AddApproveUsers]" value="{'Add users'|i18n( 'ezapprove2' )}" />

</fieldset>

{* User groups who functions as approver *}
<div class="block">
<fieldset>
<legend>{'Groups who approves content'|i18n( 'ezapprove2' )}</legend>
{if $eventData.approve_group_list}
    <table class="list" cellspacing="0">
    <tr>
        <th class="tight">&nbsp;</th>
        <th>{'Group'|i18n( 'ezapprove2' )}</th>
    </tr>
    {foreach $eventData.approve_group_list as $groupID
             sequence array( bglight, bgdark ) as $sequence}
        <tr class="{$sequence}">
            <td><input type="checkbox" name="DeleteApproveGroupIDArray_{$event.id}[]" value="{$groupID}" />
            <input type="hidden" name="WorkflowEvent_event_user_id_{$event.id}[]" value="{$groupID}" /></td>
            <td>{fetch(content, object, hash( object_id, $groupID)).name|wash}</td>
        </tr>
    {/foreach}
    </table>
{else}
    <p>{'No groups selected.'|i18n( 'ezapprove2' )}</p>
{/if}

<input class="button" type="submit" name="CustomActionButton[{$event.id}_RemoveApproveGroups]" value="{'Remove selected'|i18n( 'ezapprove2' )}"
       {section show=$eventData.approve_group_list|not}disabled="disabled"{/section} />
<input class="button" type="submit" name="CustomActionButton[{$event.id}_AddApproveGroups]" value="{'Add groups'|i18n( 'ezapprove2' )}" />

</fieldset>

</fieldset>
</div>

{* Excluded users & groups *}
<div class="block">
<fieldset>
<legend>{'Excluded user groups ( users in these groups do not need to have their content approved )'|i18n( 'ezapprove2' )}</legend>
{if $eventData.selected_usergroup_list}
<table class="list" cellspacing="0">
<tr>
<th class="tight">&nbsp;</th>
<th>{'User and user groups'|i18n( 'ezapprove2' )}</th>
</tr>
{foreach $eventData.selected_usergroup_list as $groupID
         sequence array( bglight, bgdark ) as $sequence}
<tr class="{$sequence}">
<td><input type="checkbox" name="DeleteExcludeUserIDArray_{$event.id}[]" value="{$groupID}" />
    <input type="hidden" name="WorkflowEvent_event_user_id_{$event.id}[]" value="{$groupID}" /></td>
<td>{fetch(content, object, hash( object_id, $groupID)).name|wash}</td>
</tr>
{/foreach}
</table>
{else}
<p>{'No groups selected.'|i18n( 'ezapprove2' )}</p>
{/if}

<input class="button" type="submit" name="CustomActionButton[{$event.id}_RemoveExcludeUser]" value="{'Remove selected'|i18n( 'ezapprove2' )}"
       {section show=$eventData.selected_usergroup_list|not}disabled="disabled"{/section} />
<input class="button" type="submit" name="CustomActionButton[{$event.id}_AddExcludeUser]" value="{'Add groups'|i18n( 'ezapprove2' )}" />

</fieldset>
</div>

</div>
