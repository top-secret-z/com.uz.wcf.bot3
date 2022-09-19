<section>
    <!-- contains only general -->
    {foreach from=$userBotConditions key='conditionGroup' item='conditionObjectTypes'}
        <div id="userBot_{$conditionGroup}">
            <section class="section">
                {foreach from=$conditionObjectTypes item='condition'}
                    {@$condition->getProcessor()->getHtml()}
                {/foreach}
            </section>
        </div>
    {/foreach}
</section>
