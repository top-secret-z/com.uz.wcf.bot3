{include file='header' pageTitle='wcf.acp.uzbot.log.list'}

<header class="contentHeader">
    <div class="contentHeaderTitle">
        <h1 class="contentTitle">{lang}wcf.acp.uzbot.log.list{/lang}{if $items} <span class="badge badgeInverse">{#$items}</span>{/if}</h1>
    </div>

    {hascontent}
        <nav class="contentHeaderNavigation">
            <ul>
                {content}
                    {if $objects|count}
                        <li><a title="{lang}wcf.acp.uzbot.log.clear{/lang}" class="button jsUzbotLogClear"><span class="icon icon16 fa-times"></span> <span>{lang}wcf.acp.uzbot.log.clear{/lang}</span></a></li>
                    {/if}

                    {event name='contentHeaderNavigation'}
                {/content}
            </ul>
        </nav>
    {/hascontent}
</header>

{if $availableBots|count > 1}
    <form method="post" action="{link controller='UzbotLogList'}{/link}">
        <section class="section">
            <h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>

            <div class="row rowColGap formGrid">
                <dl class="col-xs-12 col-md-4">
                    <dt></dt>
                    <dd>
                        <select name="botName" id="botName">
                            <option value="">{lang}wcf.acp.uzbot.log.botTitle{/lang}</option>
                            {htmlOptions options=$availableBots selected=$botName}
                        </select>
                    </dd>
                </dl>

                {if $availableStatus|count > 1}
                    <dl class="col-xs-12 col-md-4">
                        <dt></dt>
                        <dd>
                            <select name="botStatus" id="botStatus">
                                <option value="">{lang}wcf.acp.uzbot.log.status{/lang}</option>
                                {htmlOptions options=$availableStatus selected=$botStatus}
                            </select>
                        </dd>
                    </dl>
                {/if}

                <dl class="col-xs-12 col-md-4">
                    <dt></dt>
                    <dd>
                        <select name="botTestModus" id="botTestModus">
                            <option value="">{lang}wcf.acp.uzbot.log.testMode{/lang}</option>
                            {htmlOptions options=$availableTestModus selected=$botTestModus}
                        </select>
                    </dd>
                </dl>

                {if $availableActions|count > 1}
                    <dl class="col-xs-12 col-md-4">
                        <dt></dt>
                        <dd>
                            <select name="botAction" id="botAction">
                                <option value="">{lang}wcf.acp.uzbot.log.typeDes{/lang}</option>
                                {htmlOptions options=$availableActions selected=$botAction}
                            </select>
                        </dd>
                    </dl>
                {/if}

                {if $availableNotifies|count > 1}
                    <dl class="col-xs-12 col-md-4">
                        <dt></dt>
                        <dd>
                            <select name="botNotify" id="botNotify">
                                <option value="">{lang}wcf.acp.uzbot.log.notifyDes{/lang}</option>
                                {htmlOptions options=$availableNotifies selected=$botNotify}
                            </select>
                        </dd>
                    </dl>
                {/if}
            </div>

            <div class="formSubmit">
                <input type="submit" value="{lang}wcf.global.button.submit{/lang}" accesskey="s">
                {csrfToken}
            </div>
        </section>
    </form>
{/if}

{hascontent}
    <div class="paginationTop">
        {content}
            {assign var='linkParameters' value=''}
            {if $botAction}{capture append=linkParameters}&botAction={@$botAction|rawurlencode}{/capture}{/if}
            {if $botName}{capture append=linkParameters}&botName={@$botName|rawurlencode}{/capture}{/if}
            {if $botStatus}{capture append=linkParameters}&botStatus={@$botStatus|rawurlencode}{/capture}{/if}
            {if $botNotify}{capture append=linkParameters}&botNotify={@$botNotify|rawurlencode}{/capture}{/if}
            {if $botTestModus}{capture append=linkParameters}&botTestModus={@$botTestModus|rawurlencode}{/capture}{/if}

            {pages print=true assign=pagesLinks controller="UzbotLogList" link="pageNo=%d&sortField=$sortField&sortOrder=$sortOrder$linkParameters"}
        {/content}
    </div>
{/hascontent}

{if $objects|count}
    <div class="section tabularBox">
        <table class="table">
            <thead>
                <tr>
                    <th class="columnID columnLogID{if $sortField == 'logID'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotLogList'}pageNo={@$pageNo}&sortField=logID&sortOrder={if $sortField == 'logID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.global.objectID{/lang}</a></th>
                    <th class="columnDate columnTime{if $sortField == 'time'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotLogList'}pageNo={@$pageNo}&sortField=time&sortOrder={if $sortField == 'time' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.log.time{/lang}</a></th>
                    <th class="columnText columnStatus{if $sortField == 'status'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotLogList'}pageNo={@$pageNo}&sortField=status&sortOrder={if $sortField == 'status' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.log.status{/lang}</a></th>
                    <th class="columnTitle columnTitle{if $sortField == 'botTitle'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotLogList'}pageNo={@$pageNo}&sortField=botTitle&sortOrder={if $sortField == 'botTitle' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.log.botTitle{/lang}</a></th>
                    <th class="columnText columnType{if $sortField == 'typeDes'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotLogList'}pageNo={@$pageNo}&sortField=typeDes&sortOrder={if $sortField == 'typeDes' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.log.typeDes{/lang}</a></th>
                    <th class="columnText columnNotify{if $sortField == 'notifyDes'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotLogList'}pageNo={@$pageNo}&sortField=notifyDes&sortOrder={if $sortField == 'notifyDes' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.log.notifyDes{/lang}</a></th>
                    <th class="columnText columnCount{if $sortField == 'count'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotLogList'}pageNo={@$pageNo}&sortField=count&sortOrder={if $sortField == 'count' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.log.count{/lang}</a></th>
                    <th class="columnText columnAdditional{if $sortField == 'additionalData'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotLogList'}pageNo={@$pageNo}&sortField=additionalData&sortOrder={if $sortField == 'additionalData' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.log.additionalData{/lang}</a></th>
                </tr>
            </thead>

            <tbody>
                {foreach from=$objects item=uzbotLog}
                    <tr>
                        <td class="columnID columnLogID">{@$uzbotLog->logID}</td>
                        <td class="columnDate columnTime">{@$uzbotLog->time|time}</td>
                        <td class="columnText columnStatus">
                            {if $uzbotLog->status == 0}
                                <span class="badge green">{lang}wcf.acp.uzbot.log.ok{/lang}</span>
                            {elseif $uzbotLog->status == 1}
                                <span class="badge yellow">{lang}wcf.acp.uzbot.log.warning{/lang}</span>
                            {else}
                                <span class="badge red">{lang}wcf.acp.uzbot.log.error{/lang}</span>
                            {/if}
                            {if $uzbotLog->testMode}
                                <span class="badge blue">{lang}wcf.acp.uzbot.log.testMode{/lang}</span>
                            {/if}
                        </td>
                        <td class="columnTitle columnTitle"><a href="{link controller='UzbotEdit' id=$uzbotLog->botID}{/link}">{lang}{$uzbotLog->botTitle}{/lang}</a></td>
                        <td class="columnText columnType">{lang}wcf.acp.uzbot.type.{$uzbotLog->typeDes}{/lang}</td>
                        <td class="columnText columnNotify">{lang}wcf.acp.uzbot.notify.type.{$uzbotLog->notifyDes}{/lang}</td>
                        <td class="columnText columnCount">{@$uzbotLog->count}</td>
                        {if $uzbotLog->testMode == 1}
                            {assign var='temp' value=$uzbotLog->getAdditionalDataUnserialized()}
                            <td class="columnText columnAdditional">{if !$temp.0|empty}{$temp.0}<br><br>{/if}{if !$temp.1|empty}{$temp.1}<br><br>{/if}{@$temp.2}</td>
                        {else}
                            <td class="columnText columnAdditional">{lang}{$uzbotLog->additionalData}{/lang}</td>
                        {/if}
                    </tr>
                {/foreach}
            </tbody>
        </table>

    </div>

    <footer class="contentFooter">
        {hascontent}
            <div class="paginationBottom">
                {content}{@$pagesLinks}{/content}
            </div>
        {/hascontent}

        {hascontent}
            <nav class="contentFooterNavigation">
                <ul>
                    {content}
                        {if $objects|count}
                            <li><a title="{lang}wcf.acp.uzbot.log.clear{/lang}" class="button jsUzbotLogClear"><span class="icon icon16 fa-times"></span> <span>{lang}wcf.acp.uzbot.log.clear{/lang}</span></a></li>
                        {/if}

                        {event name='contentFooterNavigation'}
                    {/content}
                </ul>
            </nav>
        {/hascontent}
    </footer>
{else}
    <p class="info">{lang}wcf.global.noItems{/lang}</p>
{/if}

<script data-relocate="true">
    require(['Language', 'UZ/Uzbot/Acp/LogClear'], function (Language, UzbotAcpLogClear) {
        Language.addObject({
            'wcf.acp.uzbot.log.clear.confirm':        '{lang}wcf.acp.uzbot.log.clear.confirm{/lang}'
        });

        new UzbotAcpLogClear();
    });
</script>

{include file='footer'}
