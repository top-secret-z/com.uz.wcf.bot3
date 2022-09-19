<section>
    <!-- contains only general -->
    {foreach from=$receiverConditions key='conditionGroup' item='conditionObjectTypes'}
        <div id="receiver_{$conditionGroup}">
            <section class="section">
                {foreach from=$conditionObjectTypes item='condition'}
                    {@$condition->getProcessor()->getHtml()}
                {/foreach}
            </section>
        </div>
    {/foreach}
</section>
