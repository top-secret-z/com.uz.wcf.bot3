{include file='header' pageTitle='wcf.acp.uzbot.list'}

<script data-relocate="true">
    $(function() {
        new WCF.Action.Delete('wcf\\data\\uzbot\\UzbotAction', $('.jsUzBotRow'));
        new WCF.Action.Toggle('wcf\\data\\uzbot\\UzbotAction', $('.jsUzBotRow'));
    });
</script>

<header class="contentHeader">
    <div class="contentHeaderTitle">
        <h1 class="contentTitle">{lang}wcf.acp.uzbot.list{/lang}{if $items} <span class="badge badgeInverse">{#$items}</span>{/if}</h1>
    </div>

    <nav class="contentHeaderNavigation">
        <ul>
            <li><a href="{link controller='UzbotAdd'}{/link}" class="button"><span class="icon icon16 fa-plus"></span> <span>{lang}wcf.acp.uzbot.add{/lang}</span></a></li>

            {event name='contentHeaderNavigation'}
        </ul>
    </nav>
</header>

<form method="post" action="{link controller='UzbotList'}{/link}">
    <section class="section">
        <h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>

        <div class="row rowColGap formGrid">
            <dl class="col-xs-12 col-md-3">
                <dt></dt>
                <dd>
                    <input type="text" id="botTitle" name="botTitle" value="{$botTitle}" placeholder="{lang}wcf.acp.uzbot.general.botTitle{/lang}" class="long">
                </dd>
            </dl>

            {if $availableCategories|count > 1}
                <dl class="col-xs-12 col-md-3">
                    <dt></dt>
                    <dd>
                        <select name="categoryID" id="categoryID">
                            <option value="">{lang}wcf.acp.uzbot.list.categoryID{/lang}</option>
                            {htmlOptions options=$availableCategories selected=$categoryID}
                        </select>
                    </dd>
                </dl>
            {/if}

            {if $availableTypeDes|count > 1}
                <dl class="col-xs-12 col-md-3">
                    <dt></dt>
                    <dd>
                        <select name="typeDes" id="typeDes">
                            <option value="">{lang}wcf.acp.uzbot.list.typeDes{/lang}</option>
                            {htmlOptions options=$availableTypeDes selected=$typeDes}
                        </select>
                    </dd>
                </dl>
            {/if}

            {if $availableNotifyDes|count > 1}
                <dl class="col-xs-12 col-md-3">
                    <dt></dt>
                    <dd>
                        <select name="notifyDes" id="notifyDes">
                            <option value="">{lang}wcf.acp.uzbot.list.notifyDes{/lang}</option>
                            {htmlOptions options=$availableNotifyDes selected=$notifyDes}
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

{hascontent}
    <div class="paginationTop">
        {content}
            {assign var='linkParameters' value=''}
            {if $botTitle}{capture append=linkParameters}&botTitle={@$botTitle|rawurlencode}{/capture}{/if}
            {if $categoryID}{capture append=linkParameters}&categoryID={@$categoryID|rawurlencode}{/capture}{/if}
            {if $notifyDes}{capture append=linkParameters}&notifyDes={@$notifyDes|rawurlencode}{/capture}{/if}
            {if $typeDes}{capture append=linkParameters}&typeDes={@$typeDes|rawurlencode}{/capture}{/if}

            {pages print=true assign=pagesLinks controller="UzbotList" link="pageNo=%d&sortField=$sortField&sortOrder=$sortOrder$linkParameters"}
        {/content}
    </div>
{/hascontent}

{if !MODULE_UZBOT}
    <div class="warning"><strong>{lang}wcf.acp.uzbot.module_disabled{/lang}</strong></div>
{/if}

{if $objects|count}
    <div class="section tabularBox">
        <table class="table">
            <thead>
                <tr>
                    <th class="columnID columnBotID{if $sortField == 'botID'} active {@$sortOrder}{/if}" colspan="2"><a href="{link controller='UzbotList'}pageNo={@$pageNo}&sortField=botID&sortOrder={if $sortField == 'botID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.global.objectID{/lang}</a></th>
                    <th class="columnText columnActive{if $sortField == 'isDisabled'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotList'}pageNo={@$pageNo}&sortField=isDisabled&sortOrder={if $sortField == 'isDisabled' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.list.active{/lang}</a></th>
                    <th class="columnText columnTestmode{if $sortField == 'testMode'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotList'}pageNo={@$pageNo}&sortField=testMode&sortOrder={if $sortField == 'testMode' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.list.testMode{/lang}</a></th>
                    <th class="columnText columnLog{if $sortField == 'enableLog'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotList'}pageNo={@$pageNo}&sortField=enableLog&sortOrder={if $sortField == 'enableLog' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.list.log{/lang}</a></th>
                    <th class="columnText columnCategory{if $sortField == 'categoryID'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotList'}pageNo={@$pageNo}&sortField=categoryID&sortOrder={if $sortField == 'categoryID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.list.categoryID{/lang}</a></th>
                    <th class="columnText columnTitle{if $sortField == 'botTitle'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotList'}pageNo={@$pageNo}&sortField=botTitle&sortOrder={if $sortField == 'botTitle' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.list.botTitle{/lang}</a></th>
                    <th class="columnText columnType{if $sortField == 'typeDes'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotList'}pageNo={@$pageNo}&sortField=typeDes&sortOrder={if $sortField == 'typeDes' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.list.typeDes{/lang}</a></th>
                    <th class="columnText columnNotify{if $sortField == 'notifyDes'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotList'}pageNo={@$pageNo}&sortField=notifyDes&sortOrder={if $sortField == 'notifyDes' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.list.notifyDes{/lang}</a></th>
                    <th class="columnText columnSender{if $sortField == 'sendername'} active {@$sortOrder}{/if}"><a href="{link controller='UzbotList'}pageNo={@$pageNo}&sortField=sendername&sortOrder={if $sortField == 'sendername' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@$linkParameters}{/link}">{lang}wcf.acp.uzbot.list.sendername{/lang}</a></th>
                </tr>
            </thead>

            <tbody>
                {foreach from=$objects item=uzbot}
                    <tr class="jsUzBotRow">
                        <td class="columnIcon">
                            <span class="icon icon16 fa-{if !$uzbot->isDisabled}check-{/if}square-o jsToggleButton jsTooltip pointer" title="{lang}wcf.global.button.{if $uzbot->isDisabled}enable{else}disable{/if}{/lang}" data-object-id="{@$uzbot->botID}" data-disable-message="{lang}wcf.global.button.disable{/lang}" data-enable-message="{lang}wcf.global.button.enable{/lang}"></span>
                            <a href="{link controller='UzbotEdit' object=$uzbot}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip"><span class="icon icon16 fa-pencil"></span></a>
                            <span class="icon icon16 fa-remove jsDeleteButton jsTooltip pointer" title="{lang}wcf.global.button.delete{/lang}" data-object-id="{@$uzbot->botID}" data-confirm-message="{lang}wcf.acp.uzbot.delete.sure{/lang}"></span>
                        </td>
                        <td class="columnID">{@$uzbot->botID}</td>
                        <td class="columnText columnActive">{lang}wcf.acp.uzbot.{if $uzbot->isDisabled}no{else}yes{/if}{/lang}</td>
                        <td class="columnText columnTestmode">{lang}wcf.acp.uzbot.{if $uzbot->testMode}yes{else}no{/if}{/lang}</td>
                        <td class="columnText columnLog">{lang}wcf.acp.uzbot.{if $uzbot->enableLog}yes{else}no{/if}{/lang}</td>
                        {if $uzbot->categoryID}
                            <td class="columnText columnCategory">{lang}{$categories[$uzbot->categoryID]->getTitle()}{/lang}</td>
                        {else}
                            <td class="columnText columnCategory">{lang}wcf.acp.uzbot.list.categoryID.none{/lang}</td>
                        {/if}
                        <td class="columnText columnTitle">{lang}{$uzbot->botTitle}{/lang}</td>
                        <td class="columnText columnType">{lang}wcf.acp.uzbot.type.{$uzbot->typeDes}{/lang}</td>
                        <td class="columnText columnNotify">{lang}wcf.acp.uzbot.notify.type.{$uzbot->notifyDes}{/lang}</td>
                        <td class="columnText columnSender">{if $uzbot->notifyID}{$uzbot->sendername}{/if}</td>
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

        <nav class="contentFooterNavigation">
            <ul>
                <li><a href="{link controller='UzbotAdd'}{/link}" class="button"><span class="icon icon16 fa-plus"></span> <span>{lang}wcf.acp.uzbot.add{/lang}</span></a></li>

                {event name='contentFooterNavigation'}
            </ul>
        </nav>
    </footer>
{else}
    <p class="info">{lang}wcf.global.noItems{/lang}</p>
{/if}

{include file='footer'}
