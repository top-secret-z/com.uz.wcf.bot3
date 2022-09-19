<header class="sectionHeader">
    <h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
</header>

<dl{if $errorField == 'cirStartTime'} class="formError"{/if}>
    <dt><label for="cirStartTime">{lang}wcf.acp.uzbot.circular.start{/lang}</label></dt>
    <dd>
        <input type="datetime" id="cirStartTime" name="cirStartTime" value="{$cirStartTime}" class="medium" data-ignore-timezone="true" data-disable-clear="true">
        {if $errorField == 'cirStartTime'}
            <small class="innerError">
                {if $errorType == 'empty'}
                    {lang}wcf.global.form.error.empty{/lang}
                {else}
                    {lang}wcf.acp.uzbot.circular.start.error.{@$errorType}{/lang}
                {/if}
            </small>
        {/if}
    </dd>
</dl>

<dl{if $errorField == 'cirTimezone'} class="formError"{/if}>
        <dt><label for="cirTimezone">{lang}wcf.acp.uzbot.circular.timezone{/lang}</label></dt>
        <dd>
            <select name="cirTimezone" id="cirTimezone">
                {htmlOptions options=$availableTimezones selected=$cirTimezone}
            </select>
            {if $errorField == 'cirTimezone'}
                <small class="innerError">
                    {if $errorType == 'empty'}
                        {lang}wcf.global.form.error.empty{/lang}
                    {else}
                        {lang}wcf.acp.uzbot.circular.timezone.error.{@$errorType}{/lang}
                    {/if}
                </small>
            {/if}
        </dd>
    </dl>

<!-- cirRepeatType -->
<dl id="cirRepeatTypeSelector">
    <dt><label for="cirRepeatType">{lang}wcf.acp.uzbot.circular.repeatType{/lang}</label></dt>
    <dd>
        <select name="cirRepeatType" id="cirRepeatType">
            <option value="none"{if $cirRepeatType == 'none'} selected{/if}>{lang}wcf.acp.uzbot.circular.repeatType.none{/lang}</option>
            <option value="hourly"{if $cirRepeatType == 'hourly'} selected{/if}>{lang}wcf.acp.uzbot.circular.repeatType.hourly{/lang}</option>
            <option value="halfDaily"{if $cirRepeatType == 'halfDaily'} selected{/if}>{lang}wcf.acp.uzbot.circular.repeatType.halfDaily{/lang}</option>
            <option value="daily"{if $cirRepeatType == 'daily'} selected{/if}>{lang}wcf.acp.uzbot.circular.repeatType.daily{/lang}</option>
            <option value="weekly"{if $cirRepeatType == 'weekly'} selected{/if}>{lang}wcf.acp.uzbot.circular.repeatType.weekly{/lang}</option>
            <option value="monthlyDoM"{if $cirRepeatType == 'monthlyDoM'} selected{/if}>{lang}wcf.acp.uzbot.circular.repeatType.monthlyDoM{/lang}</option>
            <option value="monthlyDoW"{if $cirRepeatType == 'monthlyDoW'} selected{/if}>{lang}wcf.acp.uzbot.circular.repeatType.monthlyDoW{/lang}</option>
            <option value="quarterly"{if $cirRepeatType == 'quarterly'} selected{/if}>{lang}wcf.acp.uzbot.circular.repeatType.quarterly{/lang}</option>
            <option value="halfyearly"{if $cirRepeatType == 'halfyearly'} selected{/if}>{lang}wcf.acp.uzbot.circular.repeatType.halfyearly{/lang}</option>
            <option value="yearlyDoM"{if $cirRepeatType == 'yearlyDoM'} selected{/if}>{lang}wcf.acp.uzbot.circular.repeatType.yearlyDoM{/lang}</option>
            <option value="yearlyDoW"{if $cirRepeatType == 'yearlyDoW'} selected{/if}>{lang}wcf.acp.uzbot.circular.repeatType.yearlyDoW{/lang}</option>
        </select>
    </dd>
</dl>

<dl id="weekly">
    <dt><label for="cirWeekly_day">{lang}wcf.acp.uzbot.circular.weekday{/lang}</label></dt>
    <dd>
        <select id="cirWeekly_day" name="cirWeekly_day">
            {foreach from=$availableWeekdays key=key item=day}
                <option value="{$key}"{if $cirWeekly_day == $key} selected{/if}>{lang}wcf.date.day.{$day}{/lang}</option>
            {/foreach}
        </select>
    </dd>
</dl>

<dl id="monthlyDoM">
    <dt><label for="cirMonthlyDoM_day">{lang}wcf.acp.uzbot.circular.dayOfMonth{/lang}</label></dt>
    <dd>
        <select id="cirMonthlyDoM_day" name="cirMonthlyDoM_day">
            {section name=dayOfMonth start=1 loop=32}
                <option value="{$dayOfMonth}"{if $dayOfMonth == $cirMonthlyDoM_day} selected{/if}>{$dayOfMonth}</option>
            {/section}
        </select>
    </dd>
</dl>

<dl id="monthlyDoW">
    <dt><label for="monthlyDoW">{lang}wcf.acp.uzbot.circular.weekday{/lang}</label></dt>
    <dd>
        <select name="cirMonthlyDoW_index">
            <option value="1"{if $cirMonthlyDoW_index == 1} selected{/if}>{lang}wcf.acp.uzbot.circular.first{/lang}</option>
            <option value="2"{if $cirMonthlyDoW_index == 2} selected{/if}>{lang}wcf.acp.uzbot.circular.second{/lang}</option>
            <option value="3"{if $cirMonthlyDoW_index == 3} selected{/if}>{lang}wcf.acp.uzbot.circular.third{/lang}</option>
            <option value="4"{if $cirMonthlyDoW_index == 4} selected{/if}>{lang}wcf.acp.uzbot.circular.fourth{/lang}</option>
            <option value="-1"{if $cirMonthlyDoW_index == -1} selected{/if}>{lang}wcf.acp.uzbot.circular.last{/lang}</option>
        </select>
        <select id="cirMonthlyDoW_day" name="cirMonthlyDoW_day">
            {foreach from=$availableWeekdays key=key item=day}
                <option value="{$key}"{if $cirMonthlyDoW_day == $key} selected{/if}>{lang}wcf.date.day.{$day}{/lang}</option>
            {/foreach}
        </select>
    </dd>
</dl>

<dl id="yearlyDoM">
    <dt><label for="yearlyDoM">{lang}wcf.acp.uzbot.circular.dayOfMonth{/lang}</label></dt>
    <dd>
        <select id="cirYearlyDoM_day" name="cirYearlyDoM_day">
            {section name=dayOfMonth start=1 loop=32}
                <option value="{$dayOfMonth}"{if $dayOfMonth == $cirYearlyDoM_day} selected{/if}>{$dayOfMonth}</option>
            {/section}
        </select>
        <select name="cirYearlyDoM_month">
            <option value="1"{if $cirYearlyDoM_month == 1} selected{/if}>{lang}wcf.date.month.january{/lang}</option>
            <option value="2"{if $cirYearlyDoM_month == 2} selected{/if}>{lang}wcf.date.month.february{/lang}</option>
            <option value="3"{if $cirYearlyDoM_month == 3} selected{/if}>{lang}wcf.date.month.march{/lang}</option>
            <option value="4"{if $cirYearlyDoM_month == 4} selected{/if}>{lang}wcf.date.month.april{/lang}</option>
            <option value="5"{if $cirYearlyDoM_month == 5} selected{/if}>{lang}wcf.date.month.may{/lang}</option>
            <option value="6"{if $cirYearlyDoM_month == 6} selected{/if}>{lang}wcf.date.month.june{/lang}</option>
            <option value="7"{if $cirYearlyDoM_month == 7} selected{/if}>{lang}wcf.date.month.july{/lang}</option>
            <option value="8"{if $cirYearlyDoM_month == 8} selected{/if}>{lang}wcf.date.month.august{/lang}</option>
            <option value="9"{if $cirYearlyDoM_month == 9} selected{/if}>{lang}wcf.date.month.september{/lang}</option>
            <option value="10"{if $cirYearlyDoM_month == 10} selected{/if}>{lang}wcf.date.month.october{/lang}</option>
            <option value="11"{if $cirYearlyDoM_month == 11} selected{/if}>{lang}wcf.date.month.november{/lang}</option>
            <option value="12"{if $cirYearlyDoM_month == 12} selected{/if}>{lang}wcf.date.month.december{/lang}</option>
        </select>
    </dd>
</dl>

<dl id="yearlyDoW">
    <dt><label for="yearlyDoW">{lang}wcf.acp.uzbot.circular.weekday{/lang}</label></dt>
    <dd>
        <select name="cirYearlyDoW_index">
            <option value="1"{if $cirYearlyDoW_index == 1} selected{/if}>{lang}wcf.acp.uzbot.circular.first{/lang}</option>
            <option value="2"{if $cirYearlyDoW_index == 2} selected{/if}>{lang}wcf.acp.uzbot.circular.second{/lang}</option>
            <option value="3"{if $cirYearlyDoW_index == 3} selected{/if}>{lang}wcf.acp.uzbot.circular.third{/lang}</option>
            <option value="4"{if $cirYearlyDoW_index == 4} selected{/if}>{lang}wcf.acp.uzbot.circular.fourth{/lang}</option>
            <option value="5"{if $cirYearlyDoW_index == 5} selected{/if}>{lang}wcf.acp.uzbot.circular.last{/lang}</option>
        </select>
        <select id="cirYearlyDoW_day" name="cirYearlyDoW_day">
            {foreach from=$availableWeekdays key=key item=day}
                <option value="{$key}"{if $cirYearlyDoW_day == $key} selected{/if}>{lang}wcf.date.day.{$day}{/lang}</option>
            {/foreach}
        </select>
        <select name="cirYearlyDoW_month">
            <option value="1"{if $cirYearlyDoW_month == 1} selected{/if}>{lang}wcf.date.month.january{/lang}</option>
            <option value="2"{if $cirYearlyDoW_month == 2} selected{/if}>{lang}wcf.date.month.february{/lang}</option>
            <option value="3"{if $cirYearlyDoW_month == 3} selected{/if}>{lang}wcf.date.month.march{/lang}</option>
            <option value="4"{if $cirYearlyDoW_month == 4} selected{/if}>{lang}wcf.date.month.april{/lang}</option>
            <option value="5"{if $cirYearlyDoW_month == 5} selected{/if}>{lang}wcf.date.month.may{/lang}</option>
            <option value="6"{if $cirYearlyDoW_month == 6} selected{/if}>{lang}wcf.date.month.june{/lang}</option>
            <option value="7"{if $cirYearlyDoW_month == 7} selected{/if}>{lang}wcf.date.month.july{/lang}</option>
            <option value="8"{if $cirYearlyDoW_month == 8} selected{/if}>{lang}wcf.date.month.august{/lang}</option>
            <option value="9"{if $cirYearlyDoW_month == 9} selected{/if}>{lang}wcf.date.month.september{/lang}</option>
            <option value="10"{if $cirYearlyDoW_month == 10} selected{/if}>{lang}wcf.date.month.october{/lang}</option>
            <option value="11"{if $cirYearlyDoW_month == 11} selected{/if}>{lang}wcf.date.month.november{/lang}</option>
            <option value="12"{if $cirYearlyDoW_month == 12} selected{/if}>{lang}wcf.date.month.december{/lang}</option>
        </select>
    </dd>
</dl>

<!-- repeatCount -->
<dl id="repeatCount">
    <dt><label for="cirRepeatCount">{lang}wcf.acp.uzbot.circular.repeatCount{/lang}</label></dt>
    <dd>
        <input type="number" id="cirRepeatCount" name="cirRepeatCount" value="{@$cirRepeatCount}" class="tiny" min="1" max="1000">
    </dd>
</dl>

<!-- cirCounter -->
<dl>
    <dt><label for="cirCounter">{lang}wcf.acp.uzbot.circular.counter{/lang}</label></dt>
    <dd>
        <input type="number" id="cirCounter" name="cirCounter" value="{@$cirCounter}" class="tiny">
        &nbsp;&nbsp;
        <input type="number" id="cirCounterInterval" name="cirCounterInterval" value="{@$cirCounterInterval}" class="tiny">
        <small>{lang}wcf.acp.uzbot.circular.counter.description{/lang}</small>
    </dd>
</dl>

<script data-relocate="true">
    $(function() {
        // show / hide individual repeat settings
        $('#cirRepeatType').change(function(event) {
            $('#weekly, #monthlyDoM, #monthlyDoW, #yearlyDoM, #yearlyDoW, #repeatCount, #cirCounter').hide();

            var $value = $(event.currentTarget).val();

            if ($value != 'none') {
                $('#repeatCount, #cirCounter').show();
            }

            switch ($value) {
                case 'weekly':
                    $('#weekly, #repeatCount').show();
                    break;
                case 'monthlyDoM':
                    $('#monthlyDoM, #repeatCount').show();
                    break;
                case 'monthlyDoW':
                    $('#monthlyDoW, #repeatCount').show();
                    break;
                case 'yearlyDoM':
                    $('#yearlyDoM, #repeatCount').show();
                    break;
                case 'yearlyDoW':
                    $('#yearlyDoW, #repeatCount').show();
                    break;
            }
        });
        $('#cirRepeatType').trigger('change');
    });
</script>
