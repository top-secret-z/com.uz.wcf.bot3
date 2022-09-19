<div class="section tabMenuContainer">
    <nav class="tabMenu">
        <ul>
            {foreach from=$userConditions key='conditionGroup' item='conditionObjectTypes'}
                {assign var='__anchor' value='user_'|concat:$conditionGroup}
                <li><a href="{@$__wcf->getAnchor($__anchor)}"><span style="font-size:20px;">{lang}wcf.user.condition.conditionGroup.{$conditionGroup}{/lang}</span></a></li>
            {/foreach}
        </ul>
    </nav>

    {foreach from=$userConditions key='conditionGroup' item='conditionObjectTypes'}
        <div id="user_{$conditionGroup}" class="tabMenuContent">
            {if $conditionGroup != 'userOptions'}
                <section class="section">
                    <h2 class="sectionTitle">{lang}wcf.user.condition.conditionGroup.{$conditionGroup}{/lang}</h2>
            {/if}

            {foreach from=$conditionObjectTypes item='condition'}
                {@$condition->getProcessor()->getHtml()}
            {/foreach}

            {if $conditionGroup != 'userOptions'}
                </section>
            {/if}
        </div>
    {/foreach}
</div>
