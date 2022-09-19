{include file='header' pageTitle='wcf.acp.uzbot.'|concat:$action}

<script data-relocate="true">
	require(['WoltLabSuite/Core/Ui/User/Search/Input'], function(UiUserSearchInput) {
		new UiUserSearchInput(elBySel('input[name="sendername"]'));
	});
</script>
<script data-relocate="true">
	require(['Language', 'UZ/Uzbot/Acp/Help'], function(Language, UzbotAcpHelp) {
		Language.addObject({
			'wcf.acp.uzbot.help': '{lang}wcf.acp.uzbot.help{/lang}'
		});
		new UzbotAcpHelp();
	});
</script>

{if $__wcf->session->getPermission('admin.content.cms.canUseMedia')}
	<script data-relocate="true">
		{include file='mediaJavaScript'}
		
		require(['WoltLabSuite/Core/Media/Manager/Select'], function(MediaManagerSelect) {
			new MediaManagerSelect({
				dialogTitle: '{lang}wcf.media.chooseImage{/lang}',
				imagesOnly: 1
			});
		});
	</script>
{/if}

{if $action == 'edit'}
	<script data-relocate="true">
		require(['Language', 'UZ/Uzbot/Acp/FeedReset'], function(Language, UzbotAcpFeedReset) {
			Language.addObject({
				'wcf.acp.uzbot.feedreader.reset.confirm': '{lang}wcf.acp.uzbot.feedreader.reset.confirm{/lang}'
			});
			new UzbotAcpFeedReset();
		});
	</script>
	<script data-relocate="true">
		require(['Language', 'UZ/Uzbot/Acp/Copy'], function(Language, UzbotAcpCopy) {
			Language.addObject({
				'wcf.acp.uzbot.copy.confirm': '{lang}wcf.acp.uzbot.copy.confirm{/lang}'
			});
			new UzbotAcpCopy();
		});
	</script>
{/if}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}wcf.acp.uzbot.{$action}{/lang}</h1>
	</div>
	
	<nav class="contentHeaderNavigation">
		<ul>
			{if $action == 'edit'}
				<li class="dropdown">
					<a class="button dropdownToggle"><span class="icon icon16 fa-sort"></span> <span>{lang}wcf.acp.uzbot.choose{/lang}</span></a>
					<div class="dropdownMenu">
						<ul class="scrollableDropdownMenu">
							{foreach from=$availableBots item='availableBot'}
								<li{if $availableBot->botID == $botID} class="active"{/if}><a href="{link controller='UzbotEdit' id=$availableBot->botID}{/link}">{$availableBot->getTitle()}</a></li>
							{/foreach}
						</ul>
					</div>
				</li>
				
				<li><a class="jsButtonBotCopy button" data-object-id="{@$botID}"><span class="icon icon16 fa-files-o"></span> <span>{lang}wcf.acp.uzbot.copy{/lang}</span></a></li>
			{/if}
			<li><a href="{link controller='UzbotList'}{/link}" class="button"><span class="icon icon16 fa-list"></span> <span>{lang}wcf.acp.uzbot.list{/lang}</span></a></li>
			
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{if !MODULE_UZBOT}
	<div class="warning"><strong>{lang}wcf.acp.uzbot.module_disabled{/lang}</strong></div>
{/if}

{include file='formError'}

{if $success|isset}
	<p class="success">{lang}wcf.global.success.{@$action}{/lang}</p>
{/if}

<form id="formContainer" method="post" action="{if $action == 'add'}{link controller='UzbotAdd'}{/link}{else}{link controller='UzbotEdit' id=$uzbot->botID}{/link}{/if}">
	<div class="section tabMenuContainer">
		<nav class="tabMenu">
			<ul>
				<li><a href="{@$__wcf->getAnchor('generalData')}">{lang}wcf.acp.uzbot.general{/lang}</a></li>
				<li><a href="{@$__wcf->getAnchor('typeData')}">{lang}wcf.acp.uzbot.type{/lang}</a></li>
				<li><a href="{@$__wcf->getAnchor('notifyData')}">{lang}wcf.acp.uzbot.notify{/lang}</a></li>
			</ul>
		</nav>
		
		<!-- TAB - General Data -->
		<div id="generalData" class="tabMenuContent hidden">
			<div class="section">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.general{/lang}</h2>
					<p class="sectionDescription">{lang}wcf.acp.uzbot.general.description{/lang}</p>
				</header>
				
				<dl{if $errorField == 'botTitle'} class="formError"{/if}>
					<dt><label for="botTitle">{lang}wcf.acp.uzbot.general.botTitle{/lang}</label></dt>
					<dd>
						<input type="text" id="botTitle" name="botTitle" value="{$botTitle}" maxlength="80" class="long" />
						<small>{lang}wcf.acp.uzbot.general.botTitle.description{/lang}</small>
						
						{if $errorField == 'botTitle'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.general.botTitle.error.{@$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
				
				<dl{if $errorField == 'botDescription'} class="formError"{/if}>
					<dt><label for="botDescription">{lang}wcf.acp.uzbot.general.botDescription{/lang}</label></dt>
					<dd>
						<textarea id="botDescription" name="botDescription" cols="40" rows="2">{$botDescription}</textarea>
						<small>{lang}wcf.acp.uzbot.general.botDescription.description{/lang}</small>
						
						{if $errorField == 'botDescription'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.general.botDescription.error.{@$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
				
				<dl{if $errorField == 'categoryID'} class="formError"{/if}>
					<dt><label for="categoryID">{lang}wcf.acp.uzbot.general.categoryID{/lang}</label></dt>
					<dd>
						<select id="categoryID" name="categoryID">
							
							{include file='categoryOptionList'}
						</select>
						<small>{lang}wcf.acp.uzbot.general.categoryID.description{/lang}</small>
						
						{if $errorField == 'categoryID'}
							<small class="innerError">
								{if $errorType == 'empty'}
									{lang}wcf.global.form.error.empty{/lang}
								{else}
									{lang}wcf.acp.uzbot.general.categoryID.error.{@$errorType}{/lang}
								{/if}
							</small>
						{/if}
					</dd>
				</dl>
				
				<dl>
					<dt></dt>
					<dd>
						<label><input type="checkbox" name="isDisabled" value="1"{if $isDisabled} checked{/if}> {lang}wcf.acp.uzbot.general.isDisabled{/lang}</label>
					</dd>
				</dl>
				
				<dl{if $errorField == 'testMode'} class="formError"{/if}>
					<dt></dt>
					<dd>
						<label><input type="checkbox" name="testMode" value="1"{if $testMode} checked{/if}> {lang}wcf.acp.uzbot.general.testMode{/lang}</label>
						<small>{lang}wcf.acp.uzbot.general.testMode.description{/lang}</small>
						
						{if $errorField == 'testMode'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.general.testMode.error.{@$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
				
				<dl>
					<dt></dt>
					<dd>
						<label><input type="checkbox" name="enableLog" value="1"{if $enableLog} checked{/if}> {lang}wcf.acp.uzbot.general.enableLog{/lang}</label>
					</dd>
				</dl>
			</div>
		</div>
		
		<!-- TAB - Type Data -->
		<div id="typeData" class="tabMenuContent hidden">
			<div class="section">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.type{/lang}</h2>
					<p class="sectionDescription">{lang}wcf.acp.uzbot.type.description{/lang}</p>
				</header>
				
				<!-- typeID -->
				<dl{if $errorField == 'typeID'} class="formError"{/if}>
					<dt><label for="typeID">{lang}wcf.acp.uzbot.type.typeID{/lang}</label></dt>
					<dd>
						<select name="typeID" id="typeID">
							<option value="0">{lang}wcf.global.noSelection{/lang}</option>
							{foreach from=$availableTypes item=type}
								<option value="{@$type->typeID}"{if $type->typeID == $typeID} selected="selected"{/if}>{$type->getTitle()}</option>
							{/foreach}
						</select>
						<span class="icon icon24 fa-question-circle-o jsUzbotHelp helpType pointer" data-help-item="default"></span>
						{if $errorField == 'typeID'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.type.typeID.error.{@$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
			</div>
			
			<div class="section feedreaderSetting">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<dl{if $errorField == 'feedreaderUrl'} class="formError"{/if}>
					<dt><label for="feedreaderUrl">{lang}wcf.acp.uzbot.feedreader.url{/lang}</label></dt>
					<dd>
						<input type="text" id="feedreaderUrl" name="feedreaderUrl" value="{$feedreaderUrl}" class="long" />
						
						{if $errorField == 'feedreaderUrl'}
							<small class="innerError">
								{if $errorType == 'empty'}
									{lang}wcf.global.form.error.empty{/lang}
								{else}
									{lang}wcf.acp.uzbot.feedreader.url.error.{$errorType}{/lang}
								{/if}
							</small>
						{/if}
					</dd>
				</dl>
				
				<dl>
					<dt><label for="feedreaderUseTime">{lang}wcf.acp.uzbot.feedreader.useTime{/lang}</label></dt>
					<dd>
						<label><input type="checkbox" name="feedreaderUseTime" id="feedreaderUseTime" value="1"{if $feedreaderUseTime} checked{/if}> {lang}wcf.acp.uzbot.feedreader.useTime.enable{/lang}</label>
					</dd>
				</dl>
				
				<dl>
					<dt><label for="feedreaderUseTags">{lang}wcf.acp.uzbot.feedreader.useTags{/lang}</label></dt>
					<dd>
						<label><input type="checkbox" name="feedreaderUseTags" id="feedreaderUseTags" value="1"{if $feedreaderUseTags} checked{/if}> {lang}wcf.acp.uzbot.feedreader.useTags.enable{/lang}</label>
					</dd>
				</dl>
				
				{if ($action == 'edit')}
					<dl>
						<dt><label>{lang}wcf.acp.uzbot.feedreader.reset{/lang}</label></dt>
						<dd>
							<span class="button jsUzbotFeedReset" data-object-id="{@$uzbot->botID}"><span>{lang}wcf.acp.uzbot.feedreader.reset.button{/lang}</span></span>
						</dd>
					</dl>
				{/if}
				
				<dl>
					<dt><label for="feedreaderFrequency">{lang}wcf.acp.uzbot.feedreader.frequency{/lang}</label></dt>
					<dd>
						<select name="feedreaderFrequency" id="feedreaderFrequency">
							<option value="0"{if $feedreaderFrequency == 0} selected="selected"{/if}>{lang}wcf.acp.uzbot.period.once{/lang}</option>
							<option value="900"{if $feedreaderFrequency == 900} selected="selected"{/if}>{lang}wcf.acp.uzbot.period.hourquarter{/lang}</option>
							<option value="1800"{if $feedreaderFrequency == 1800} selected="selected"{/if}>{lang}wcf.acp.uzbot.period.hourhalf{/lang}</option>
							<option value="3600"{if $feedreaderFrequency == 3600} selected="selected"{/if}>{lang}wcf.acp.uzbot.period.hour{/lang}</option>
							<option value="7200"{if $feedreaderFrequency == 7200} selected="selected"{/if}>{lang}wcf.acp.uzbot.period.hourtwo{/lang}</option>
							<option value="10800"{if $feedreaderFrequency == 10800} selected="selected"{/if}>{lang}wcf.acp.uzbot.period.hourthree{/lang}</option>
							<option value="21600"{if $feedreaderFrequency == 21600} selected="selected"{/if}>{lang}wcf.acp.uzbot.period.hoursix{/lang}</option>
							<option value="43200"{if $feedreaderFrequency == 43200} selected="selected"{/if}>{lang}wcf.acp.uzbot.period.dayhalf{/lang}</option>
							<option value="86400"{if $feedreaderFrequency == 86400} selected="selected"{/if}>{lang}wcf.acp.uzbot.period.day{/lang}</option>
							<option value="172800"{if $feedreaderFrequency == 172800} selected="selected"{/if}>{lang}wcf.acp.uzbot.period.daytwo{/lang}</option>
							<option value="604800"{if $feedreaderFrequency == 604800} selected="selected"{/if}>{lang}wcf.acp.uzbot.period.week{/lang}</option>
						</select>
					</dd>
				</dl>
				
				<dl>
					<dt><label for="feedreaderMaxAge">{lang}wcf.acp.uzbot.feedreader.maxAge{/lang}</label></dt>
					<dd>
						<input type="number" name="feedreaderMaxAge" id="feedreaderMaxAge" value="{$feedreaderMaxAge}" class="small" min="0" />
						<small>{lang}wcf.acp.uzbot.feedreader.maxAge.description{/lang}</small>
					</dd>
				</dl>
				
				<dl>
					<dt><label for="feedreaderMaxItems">{lang}wcf.acp.uzbot.feedreader.maxItems{/lang}</label></dt>
					<dd>
						<input type="number" name="feedreaderMaxItems" id="feedreaderMaxItems" value="{$feedreaderMaxItems}" class="small" min="0" />
						<small>{lang}wcf.acp.uzbot.feedreader.maxItems.description{/lang}</small>
					</dd>
				</dl>
				
				<dl>
					<dt><label for="feedreaderInclude">{lang}wcf.acp.uzbot.feedreader.include{/lang}</label></dt>
					<dd>
						<textarea name="feedreaderInclude" id="feedreaderInclude" rows="2" cols="60">{$feedreaderInclude}</textarea>
						<small>{lang}wcf.acp.uzbot.feedreader.include.description{/lang}</small>
					</dd>
				</dl>
				
				<dl>
					<dt><label for="feedreaderExclude">{lang}wcf.acp.uzbot.feedreader.exclude{/lang}</label></dt>
					<dd>
						<textarea name="feedreaderExclude" id="feedreaderExclude" rows="2" cols="60">{$feedreaderExclude}</textarea>
						<small>{lang}wcf.acp.uzbot.feedreader.exclude.description{/lang}</small>
					</dd>
				</dl>
			</div>
			
			<div class="section system_circular">
				{include file='__uzbotAddCircular'}
			</div>
			
			<div class="section article_change">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<dl>
					<dt><label for="articleConditionCategoryID">{lang}wcf.acp.article.category{/lang}</label></dt>
					<dd>
						<select name="articleConditionCategoryID" id="articleConditionCategoryID">
							<option value="0">{lang}wcf.global.noSelection{/lang}</option>
							
							{foreach from=$articleCategoryNodeList item=category}
								<option value="{@$category->categoryID}"{if $category->categoryID == $articleConditionCategoryID} selected{/if}>{if $category->getDepth() > 1}{@"&nbsp;&nbsp;&nbsp;&nbsp;"|str_repeat:($category->getDepth() - 1)}{/if}{$category->getTitle()}</option>
							{/foreach}
						</select>
					</dd>
				</dl>
			</div>
			
			<div class="section article_new">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<dl>
					<dt><label for="articleConditionCategoryID_new">{lang}wcf.acp.article.category{/lang}</label></dt>
					<dd>
						<select name="articleConditionCategoryID_new" id="articleConditionCategoryID_new">
							<option value="0">{lang}wcf.global.noSelection{/lang}</option>
							
							{foreach from=$articleCategoryNodeList item=category}
								<option value="{@$category->categoryID}"{if $category->categoryID == $articleConditionCategoryID_new} selected{/if}>{if $category->getDepth() > 1}{@"&nbsp;&nbsp;&nbsp;&nbsp;"|str_repeat:($category->getDepth() - 1)}{/if}{$category->getTitle()}</option>
							{/foreach}
						</select>
					</dd>
				</dl>
				
				<dl>
					<dt><label>{lang}wcf.acp.uzbot.article.articlePublished{/lang}</label></dt>
					<dd>
						<label><input type="checkbox" name="articlePublished" value="1"{if $articlePublished} checked{/if}> {lang}wcf.acp.uzbot.article.articlePublished{/lang}</label>
					</dd>
				</dl>
			</div>
			
			<div class="section system_error">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<p>{lang}wcf.acp.uzbot.type.description.notifyOnly{/lang}</p>
			</div>
			
			<div class="section system_comment">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.comment.setting{/lang}</h2>
				</header>
				
				<dl>
					<dt><label for="commentNoAnswers">{lang}wcf.acp.uzbot.comment.answers{/lang}</label></dt>
					<dd>
						<label><input type="checkbox" name="commentNoAnswers" id="commentNoAnswers" value="1"{if $commentNoAnswers} checked{/if}> {lang}wcf.acp.uzbot.comment.answers.no{/lang}</label>
					</dd>
				</dl>
				
				<dl>
					<dt><label for="commentNoUser">{lang}wcf.acp.uzbot.comment.user{/lang}</label></dt>
					<dd>
						<label><input type="checkbox" name="commentNoUser" id="commentNoUser" value="1"{if $commentNoUser} checked{/if}> {lang}wcf.acp.uzbot.comment.user.no{/lang}</label>
						<small>{lang}wcf.acp.uzbot.comment.user.no.description{/lang}</small>
					</dd>
				</dl>
				
				<dl>
					<dt><label for="commentDays">{lang}wcf.acp.uzbot.comment.days{/lang}</label></dt>
					<dd>
						<input type="number" name="commentDays" id="commentDays" value="{$commentDays}" min="1" class="small" />
					</dd>
				</dl>
				
				<dl>
					<dt></dt>
					<dd>
						<label><input type="radio" name="commentDaysAfter" value="reply"{if $commentDaysAfter == 'reply'} checked{/if} /> {lang}wcf.acp.uzbot.comment.days.reply{/lang}</label>
						<label><input type="radio" name="commentDaysAfter" value="creation"{if $commentDaysAfter == 'creation'} checked{/if} /> {lang}wcf.acp.uzbot.comment.days.creation{/lang}</label>
					</dd>
				</dl>
				
				<dl{if $errorField == 'commentTypeIDs'} class="formError"{/if}>
					<dt>{lang}wcf.acp.uzbot.comment.types{/lang}</dt>
					<dd>
						{foreach from=$availableCommentTypes item=commentType}
							<label><input type="checkbox" name="commentTypeIDs[]" value="{@$commentType->objectTypeID}"{if $commentType->objectTypeID|in_array:$commentTypeIDs} checked{/if}> {$commentType->objectType}</label>
						{/foreach}
						
						{if $errorField == 'commentTypeIDs'}
							<small class="innerError">
								{if $errorType == 'empty'}
									{lang}wcf.global.form.error.empty{/lang}
								{else}
									{lang}wcf.acp.uzbot.comment.types.error.{@$errorType}{/lang}
								{/if}
							</small>
						{/if}
					</dd>
				</dl>
			</div>
			
			<div class="section system_contact">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<p>{lang}wcf.acp.uzbot.type.description.notifyOnly{/lang}</p>
			</div>
			
			<div class="section system_conversation">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.conversation.setting{/lang}</h2>
				</header>
				
				<dl>
					<dt><label for="conversationNoAnswers">{lang}wcf.acp.uzbot.conversation.answers{/lang}</label></dt>
					<dd>
						<label><input type="checkbox" name="conversationNoAnswers" id="conversationNoAnswers" value="1"{if $conversationNoAnswers} checked{/if}> {lang}wcf.acp.uzbot.conversation.answers.no{/lang}</label>
					</dd>
				</dl>
				
				<dl>
					<dt><label for="conversationNoLabels">{lang}wcf.acp.uzbot.conversation.labels{/lang}</label></dt>
					<dd>
						<label><input type="checkbox" name="conversationNoLabels" id="conversationNoLabels" value="1"{if $conversationNoLabels} checked{/if}> {lang}wcf.acp.uzbot.conversation.labels.no{/lang}</label>
					</dd>
				</dl>
				
				<dl>
					<dt><label for="conversationDays">{lang}wcf.acp.uzbot.conversation.days{/lang}</label></dt>
					<dd>
						<input type="number" name="conversationDays" id="conversationDays" value="{$conversationDays}" min="1" class="small" />
					</dd>
				</dl>
				
				<dl>
					<dt></dt>
					<dd>
						<label><input type="radio" name="conversationDaysAfter" value="reply"{if $conversationDaysAfter == 'reply'} checked{/if} /> {lang}wcf.acp.uzbot.conversation.days.reply{/lang}</label>
						<label><input type="radio" name="conversationDaysAfter" value="creation"{if $conversationDaysAfter == 'creation'} checked{/if} /> {lang}wcf.acp.uzbot.conversation.days.creation{/lang}</label>
					</dd>
				</dl>
			</div>
			
			<div class="section system_report">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<p>{lang}wcf.acp.uzbot.type.system_report.description{/lang}</p>
			</div>
			
			<div class="section system_update">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				<p>{lang}wcf.acp.uzbot.type.description.notifyOnly{/lang}</p>
			</div>
			
			<div class="section system_statistics">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				<p>{lang}wcf.acp.uzbot.type.description.notifyOnly{/lang}</p>
			</div>
			
			<div class="section user_birthday">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<dl>
					<dt></dt>
					<dd>
						<label><input type="checkbox" name="birthdayForce" value="1"{if $birthdayForce} checked{/if}> {lang}wcf.acp.uzbot.birthday.birthdayForce{/lang}</label>
					</dd>
				</dl>
			</div>
			
			<div class="section user_trophy">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
			</div>
			
			<div class="section user_creation">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.user.creation.setting{/lang}</h2>
				</header>
				
				<dl{if $errorField == 'userCreationGroupID'} class="formError"{/if}>
					<dt><label for="userCreationGroupID">{lang}wcf.acp.uzbot.user.creation.groupID{/lang}</label></dt>
					<dd>
						<select name="userCreationGroupID" id="userCreationGroupID">
							<option value="0">{lang}wcf.global.noSelection{/lang}</option>
							{foreach from=$availableGroups item=group}
								<option value="{@$group->groupID}"{if $group->groupID == $userCreationGroupID} selected="selected"{/if}>{$group->groupName|language}</option>
							{/foreach}
						</select>
						{if $errorField == 'userCreationGroupID'}
							<small class="innerError">{lang}wcf.acp.uzbot.user.creation.groupID.error.{$errorType}{/lang}</small>
						{/if}
					</dd>
				</dl>
			</div>
			
			<div class="section groupAssignmentSetting">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<dl{if $errorField == 'groupAssignmentGroupID'} class="formError"{/if}>
					<dt>{lang}wcf.acp.uzbot.groupAssignment.groupID{/lang}</dt>
					<dd>
						{htmlOptions name='groupAssignmentGroupID' options=$availableGroups selected=$groupAssignmentGroupID}
						{if $errorField == 'groupAssignmentGroupID'}
							<small class="innerError">{lang}wcf.acp.uzbot.groupAssignment.groupID.error.{$errorType}{/lang}</small>
						{/if}
					</dd>
				</dl>
				
				<dl>
					<dt>{lang}wcf.acp.uzbot.groupAssignment.action{/lang}</dt>
					<dd>
						<label><input type="radio" name="groupAssignmentAction" value="add"{if $groupAssignmentAction == 'add'} checked{/if}> {lang}wcf.acp.uzbot.groupAssignment.action.add{/lang}</label>
						<label><input type="radio" name="groupAssignmentAction" value="remove"{if $groupAssignmentAction == 'remove'} checked{/if}> {lang}wcf.acp.uzbot.groupAssignment.action.remove{/lang}</label>
					</dd>
				</dl>
			</div>
			
			<div class="section inactiveSetting">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<dl>
					<dt>{lang}wcf.acp.uzbot.inactive.action{/lang}</dt>
					<dd>
						<label><input type="radio" name="inactiveAction" value="remind"{if $inactiveAction == 'remind'} checked{/if} /> {lang}wcf.acp.uzbot.inactive.action.remind{/lang}</label>
						<label><input type="radio" name="inactiveAction" value="unremind"{if $inactiveAction == 'unremind'} checked{/if} /> {lang}wcf.acp.uzbot.inactive.action.unremind{/lang}</label>
						<label><input type="radio" name="inactiveAction" value="deactivate"{if $inactiveAction == 'deactivate'} checked{/if} /> {lang}wcf.acp.uzbot.inactive.action.deactivate{/lang}</label>
						<label><input type="radio" name="inactiveAction" value="activate"{if $inactiveAction == 'activate'} checked{/if} /> {lang}wcf.acp.uzbot.inactive.action.activate{/lang}</label>
						<label><input type="radio" name="inactiveAction" value="ban"{if $inactiveAction == 'ban'} checked{/if} /> {lang}wcf.acp.uzbot.inactive.action.ban{/lang}</label>
						<label><input type="radio" name="inactiveAction" value="delete"{if $inactiveAction == 'delete'} checked{/if} /> {lang}wcf.acp.uzbot.inactive.action.delete{/lang}</label>
					</dd>
				</dl>
				
				<dl>
					<dt><label for="inactiveBanReason">{lang}wcf.acp.uzbot.inactive.banReason{/lang}</label></dt>
					<dd>
						<textarea id="inactiveBanReason" name="inactiveBanReason" rows="3" cols="40">{$inactiveBanReason}</textarea>
					</dd>
				</dl>
				
				<dl>
					<dt><label for="inactiveReminderLimit">{lang}wcf.acp.uzbot.inactive.reminderLimit{/lang}</label></dt>
					<dd>
						<input type="number" name="inactiveReminderLimit" id="inactiveReminderLimit" value="{$inactiveReminderLimit}" min="1" class="small" />
						<small>{lang}wcf.acp.uzbot.inactive.reminderLimit.description{/lang}</small>
					</dd>
				</dl>
			</div>
			
			<div class="section user_setting">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<dl{if $errorField == 'userSetting'} class="formError"{/if}>
					<dt>{lang}wcf.acp.uzbot.userSetting{/lang}</dt>
					<dd>
						<label><input type="checkbox" name="userSettingAvatarOption" value="1"{if $userSettingAvatarOption} checked{/if} /> {lang}wcf.acp.uzbot.userSetting.avatarOption{/lang}</label>
						<label><input type="checkbox" name="userSettingUsername" value="1"{if $userSettingUsername} checked{/if} /> {lang}wcf.acp.uzbot.userSetting.username{/lang}</label>
						<label><input type="checkbox" name="userSettingUserTitle" value="1"{if $userSettingUserTitle} checked{/if} /> {lang}wcf.acp.uzbot.userSetting.userTitle{/lang}</label>
						<label><input type="checkbox" name="userSettingEmail" value="1"{if $userSettingEmail} checked{/if} /> {lang}wcf.acp.uzbot.userSetting.email{/lang}</label>
						<label><input type="checkbox" name="userSettingSelfDeletion" value="1"{if $userSettingSelfDeletion} checked{/if} /> {lang}wcf.acp.uzbot.userSetting.selfDeletion{/lang}</label>
						<label><input type="checkbox" name="userSettingSignature" value="1"{if $userSettingSignature} checked{/if} /> {lang}wcf.acp.uzbot.userSetting.signature{/lang}</label>
						<label><input type="checkbox" name="userSettingCover" value="1"{if $userSettingCover} checked{/if} /> {lang}wcf.acp.uzbot.userSetting.cover{/lang}</label>
						<label><input type="checkbox" name="userSettingOther" value="1"{if $userSettingOther} checked{/if} /> {lang}wcf.acp.uzbot.userSetting.other{/lang}</label>
						
						{if $errorField == 'userSetting'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.userSetting.error.notConfigured{/lang}
							</small>
						{/if}
					</dd>
				</dl>
			</div>
			
			<div class="section user_likes">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<dl>
					<dt>{lang}wcf.acp.uzbot.userLike.action{/lang}</dt>
					<dd>
						<label><input type="radio" name="likeAction" value="likeTotal"{if $likeAction == 'likeTotal'} checked{/if} /> {lang}wcf.acp.uzbot.userLike.likeTotal{/lang}</label>
						<label><input type="radio" name="likeAction" value="likeX"{if $likeAction == 'likeX'} checked{/if} /> {lang}wcf.acp.uzbot.userLike.likeX{/lang}</label>
						<label><input type="radio" name="likeAction" value="likeTop"{if $likeAction == 'likeTop'} checked{/if} /> {lang}wcf.acp.uzbot.userLike.likeTop{/lang}</label>
					</dd>
				</dl>
			</div>
			
			<div class="section user_groupChange">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<dl>
					<dt>{lang}wcf.acp.uzbot.groupChange.groupChangeType{/lang}</dt>
					<dd>
						<label><input type="radio" name="groupChangeType" value="0"{if $groupChangeType == 0} checked{/if} /> {lang}wcf.acp.uzbot.groupChange.groupChangeType.both{/lang}</label>
						<label><input type="radio" name="groupChangeType" value="1"{if $groupChangeType == 1} checked{/if} /> {lang}wcf.acp.uzbot.groupChange.groupChangeType.add{/lang}</label>
						<label><input type="radio" name="groupChangeType" value="2"{if $groupChangeType == 2} checked{/if} /> {lang}wcf.acp.uzbot.groupChange.groupChangeType.remove{/lang}</label>
					</dd>
				</dl>
				
				<dl{if $errorField == 'groupChangeGroupIDs'} class="formError"{/if}>
					<dt><label>{lang}wcf.acp.uzbot.groupChange.groupChangeGroupIDs{/lang}</label></dt>
					<dd>
						<label><input type="checkbox" name="groupChangeGroupIDs[]" value="0" {if 0|in_array:$groupChangeGroupIDs} checked{/if}> {lang}wcf.acp.uzbot.groupChange.groupChangeGroupIDs.allGroups{/lang}</label>
						<br>
						{htmlCheckboxes options=$availableGroupsPassive name=groupChangeGroupIDs selected=$groupChangeGroupIDs}
						
						{if $errorField == 'groupChangeGroupIDs'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.groupChange.groupChangeGroupIDs.error.{$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
			</div>
			
			<div class="section user_warning">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<p>{lang}wcf.acp.uzbot.type.user_warning.description{/lang}</p>
			</div>
			
			<div class="section user_ban">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<p>{lang}wcf.acp.uzbot.type.description.notifyOnly{/lang}</p>
			</div>
			
			<div class="section user_unban">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.setting{/lang}</h2>
				</header>
				
				<p>{lang}wcf.acp.uzbot.type.description.notifyOnly{/lang}</p>
			</div>
			
			<div class="section" id="actionLabelContainer">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.label.action{/lang}</h2>
				</header>
				
				{if $labelGroups|count && $availableLabels|count}
					{foreach from=$labelGroups item=labelGroup}
						{if $labelGroup|count}
							<dl>
								<dt><label>{$labelGroup->getTitle()}</label></dt>
								<dd>
									<ul class="labelList jsOnly" data-object-id="{@$labelGroup->groupID}">
										<li class="dropdown actionLabelChooser" id="actionLabelGroup{@$labelGroup->groupID}" data-group-id="{@$labelGroup->groupID}" data-force-selection="false">
											<div class="dropdownToggle" data-toggle="actionLabelGroup{@$labelGroup->groupID}"><span class="badge label">{lang}wcf.label.none{/lang}</span></div>
											<div class="dropdownMenu">
												<ul class="scrollableDropdownMenu">
													{foreach from=$labelGroup item=label}
														<li data-label-id="{@$label->labelID}"><span><span class="badge label{if $label->getClassNames()} {@$label->getClassNames()}{/if}">{lang}{$label->label}{/lang}</span></span></li>
													{/foreach}
												</ul>
											</div>
										</li>
									</ul>
								</dd>
							</dl>
						{/if}
					{/foreach}
				{else}
					<p>{lang}wcf.acp.uzbot.label.error.noLabels{/lang}</p>
				{/if}
				
			</div>
			
			{event name='UzbotType'}
			
			<div class="section user_count">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.user.count.setting{/lang}</h2>
				</header>
				
				<dl{if $errorField == 'userCount'} class="formError"{/if}>
					<dt><label for="userCount">{lang}wcf.acp.uzbot.values{/lang}</label></dt>
					<dd>
						<textarea name="userCount" id="userCount" cols="40" rows="2">{$userCount}</textarea>
						<small>{lang}wcf.acp.uzbot.values.description{/lang}</small>
						
						{if $errorField == 'userCount'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.user.count.error.{$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
			</div>
			
			<div class="section condenseSetting">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.condense.setting{/lang} <span class="icon icon24 fa-question-circle-o jsUzbotHelp helpType pointer" data-help-item="condense.setting"></span></h2>
				</header>
				
				<dl>
					<dt></dt>
					<dd>
						<label><input type="checkbox" name="condenseEnable" id="condenseEnable" value="1"{if $condenseEnable} checked{/if}> {lang}wcf.acp.uzbot.condense.enable{/lang}</label>
					</dd>
				</dl>
			</div>
			
			<div class="section affectedSetting">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.affected.setting{/lang} <span class="icon icon24 fa-question-circle-o jsUzbotHelp helpType pointer" data-help-item="affected.setting"></span></h2>
				</header>
				
				<dl>
					<dt>{lang}wcf.acp.uzbot.affected{/lang}</dt>
					<dd>
						<label><input type="radio" name="changeAffected" value="0"{if $changeAffected == 0} checked{/if}> {lang}wcf.acp.uzbot.affected.standard{/lang}</label>
						<label><input type="radio" name="changeAffected" value="1"{if $changeAffected == 1} checked{/if}> {lang}wcf.acp.uzbot.affected.active{/lang}</label>
					</dd>
				</dl>
			</div>
			
			<div class="section" id="conditionLabelContainer">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.label.condition{/lang}</h2>
				</header>
				
				{if $labelGroups|count}
					{foreach from=$labelGroups item=labelGroup}
						{if $labelGroup|count}
							<dl>
								<dt><label>{$labelGroup->getTitle()}</label></dt>
								<dd>
									<ul class="labelList jsOnly" data-object-id="{@$labelGroup->groupID}">
										<li class="dropdown conditionLabelChooser" id="conditionLabelGroup{@$labelGroup->groupID}" data-group-id="{@$labelGroup->groupID}" data-force-selection="false">
											<div class="dropdownToggle" data-toggle="conditionLabelGroup{@$labelGroup->groupID}"><span class="badge label">{lang}wcf.label.none{/lang}</span></div>
											<div class="dropdownMenu">
												<ul class="scrollableDropdownMenu">
													{foreach from=$labelGroup item=label}
														<li data-label-id="{@$label->labelID}"><span><span class="badge label{if $label->getClassNames()} {@$label->getClassNames()}{/if}">{lang}{$label->label}{/lang}</span></span></li>
													{/foreach}
												</ul>
											</div>
										</li>
									</ul>
								</dd>
							</dl>
						{/if}
					{/foreach}
				{/if}
			</div>
			
			<div class="section uzbotUserBotConditions">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.condition.userBot{/lang} <span class="icon icon24 fa-question-circle-o jsUzbotHelp pointer" data-help-item="condition.userBot"></span></h2>
				</header>
				
				{include file='uzbotUserBotConditions'}
				
			</div>
			
			<div class="section uzbotUserConditions">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.condition.user{/lang} <span class="icon icon24 fa-question-circle-o jsUzbotHelp pointer" data-help-item="condition.user"></span></h2>
				</header>
				
				{include file='uzbotUserConditions'}
				
			</div>
		</div>
		
<!-- notify tab -->
		<!-- TAB - Type Data -->
		<div id="notifyData" class="tabMenuContent hidden">
			<div class="section">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.notify{/lang}</h2>
					<p class="sectionDescription">{lang}wcf.acp.uzbot.notify.description{/lang}</p>
				</header>
				
				<!-- notifyID -->
				<dl{if $errorField == 'notifyID'} class="formError"{/if}>
					<dt><label for="notifyID">{lang}wcf.acp.uzbot.notify.notifyID{/lang}</label></dt>
					<dd>
						<select name="notifyID" id="notifyID">
						<!--	<option value="0">{lang}wcf.global.noSelection{/lang}</option> -->
							{foreach from=$availableNotifies item=notify}
								<option value="{@$notify->notifyID}"{if $notify->notifyID == $notifyID} selected="selected"{/if}>{$notify->getTitle()}</option>
							{/foreach}
						</select>
						
						{if $errorField == 'notifyID'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.notify.notifyID.error.{@$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
				
				<dl{if $errorField == 'sendername'} class="formError notifySendername"{else} class="notifySenderName"{/if}>
					<dt><label for="sendername">{lang}wcf.acp.uzbot.notify.sendername{/lang}</label></dt>
					<dd>
						<input type="text" id="sendername" name="sendername" value="{$sendername}" class="medium" maxlength="255">
						<small>{lang}wcf.acp.uzbot.notify.sendername.description{/lang}</small>
						
						{if $errorField == 'sendername'}
							<small class="innerError">
								{if $errorType == 'empty'}
									{lang}wcf.global.form.error.empty{/lang}
								{else}
									{lang}wcf.acp.uzbot.notify.sendername.error.{@$errorType}{/lang}
								{/if}
							</small>
						{/if}
					</dd>
				</dl>
				
				{if $availableLanguages|count > 1}
					<dl{if $errorField == 'notifyLanguageID'} class="formError notifyLanguageID"{else} class="notifyLanguageID"{/if}>
						<dt><label for="notifyLanguageID">{lang}wcf.acp.uzbot.notify.languageID{/lang}</label></dt>
						<dd>
							<select name="notifyLanguageID" id="notifyLanguageID">
								<option value="0"{if 0 == $notifyLanguageID} selected="selected"{/if}>{lang}wcf.acp.uzbot.notify.language.auto{/lang}</option>
								<option value="-1"{if -1 == $notifyLanguageID} selected="selected"{/if}>{lang}wcf.acp.uzbot.notify.language.all{/lang}</option>
								{foreach from=$availableLanguages item=language}
									<option value="{@$language->languageID}"{if $language->languageID == $notifyLanguageID} selected="selected"{/if}>{$language->languageName|language}</option>
								{/foreach}
							</select>
							<span class="icon icon24 fa-question-circle-o jsUzbotHelp pointer" data-help-item="notify.languageID"></span>
							
							{if $errorField == 'notifyLanguageID'}
								<small class="innerError">
									{lang}wcf.acp.uzbot.notify.languageID.error.{@$errorType}{/lang}
								</small>
							{/if}
						</dd>
					</dl>
				{/if}
			</div>
			
			<div class="section notifyArticleSettings">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.notify.article{/lang}</h2>
				</header>
				
				{if !MODULE_ARTICLE}<p class="warning">{lang}wcf.acp.uzbot.notify.article.inactive{/lang}</p>{/if}
				
				<dl{if $errorField == 'articleCategoryID'} class="formError"{/if}>
					<dt><label for="articleCategoryID">{lang}wcf.acp.article.category{/lang}</label></dt>
					<dd>
						<select name="articleCategoryID" id="articleCategoryID">
							<option value="0">{lang}wcf.global.noSelection{/lang}</option>
							
							{foreach from=$articleCategoryNodeList item=category}
								<option value="{@$category->categoryID}"{if $category->categoryID == $articleCategoryID} selected{/if}>{if $category->getDepth() > 1}{@"&nbsp;&nbsp;&nbsp;&nbsp;"|str_repeat:($category->getDepth() - 1)}{/if}{$category->getTitle()}</option>
							{/foreach}
						</select>
						{if $errorField == 'articleCategoryID'}
							<small class="innerError">
								{if $errorType == 'empty'}
									{lang}wcf.global.form.error.empty{/lang}
								{else}
									{lang}wcf.acp.article.category.error.{@$errorType}{/lang}
								{/if}
							</small>
						{/if}
					</dd>
				</dl>
				
				<dl>
					<dt>{lang}wcf.acp.article.publicationStatus{/lang}</dt>
					<dd class="floated">
						<label><input type="radio" name="articlePublicationStatus" value="0"{if $articlePublicationStatus == 0} checked{/if}> {lang}wcf.acp.article.publicationStatus.unpublished{/lang}</label>
						<label><input type="radio" name="articlePublicationStatus" value="1"{if $articlePublicationStatus == 1} checked{/if}> {lang}wcf.acp.article.publicationStatus.published{/lang}</label>
					</dd>
				</dl>
				
				<dl>
					<dt></dt>
					<dd>
						<label><input name="articleEnableComments" type="checkbox" value="1"{if $articleEnableComments} checked{/if}> {lang}wcf.acp.article.enableComments{/lang}</label>
					</dd>
				</dl>
			</div>
			
			<div class="section notifyCommentSettings">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.notify.comment{/lang}</h2>
				</header>
				
				{if !MODULE_USER_PROFILE_WALL}<p class="warning">{lang}wcf.acp.uzbot.notify.comment.inactive{/lang}</p>{/if}
				
				<dl>
					<dt><label for="commentActivity">{lang}wcf.acp.uzbot.settings{/lang}</label></dt>
					<dd>
						<label><input name="commentActivity" id="commentActivity" type="checkbox" value="1"{if $commentActivity} checked{/if}> {lang}wcf.acp.uzbot.notify.comment.activity{/lang}</label>
					</dd>
				</dl>
			</div>
			
			<div class="section notifyConversationSettings">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.notify.conversation{/lang}</h2>
				</header>
				
				{if !MODULE_CONVERSATION}<p class="warning">{lang}wcf.acp.uzbot.notify.conversation.inactive{/lang}</p>{/if}
				
				<dl>
					<dt>{lang}wcf.acp.uzbot.notify.conversation.type{/lang}</dt>
					<dd class="floated">
						<label><input type="radio" name="conversationType" value="0"{if $conversationType == 0} checked{/if}> {lang}wcf.acp.uzbot.notify.conversation.type.individual{/lang}</label>
						<label><input type="radio" name="conversationType" value="1"{if $conversationType == 1} checked{/if}> {lang}wcf.acp.uzbot.notify.conversation.type.group{/lang}</label>
						<small>{lang}wcf.acp.uzbot.notify.conversation.type.description{/lang}</small>
					</dd>
				</dl>
				
				<dl>
					<dt>{lang}wcf.acp.uzbot.settings{/lang}</dt>
					<dd>
						<label><input type="radio" name="conversationLeave" value="0"{if $conversationLeave == 0} checked{/if}> {lang}wcf.acp.uzbot.notify.conversation.leave.no{/lang}</label>
						<label><input type="radio" name="conversationLeave" value="1"{if $conversationLeave == 1} checked{/if}> {lang}wcf.acp.uzbot.notify.conversation.leave{/lang}</label>
						<label><input type="radio" name="conversationLeave" value="2"{if $conversationLeave == 2} checked{/if}> {lang}wcf.acp.uzbot.notify.conversation.leave.full{/lang}</label>
						<small>{lang}wcf.acp.uzbot.notify.conversation.leave.description{/lang}</small>
						<label><input name="conversationClose" type="checkbox" value="1"{if $conversationClose} checked{/if}> {lang}wcf.acp.uzbot.notify.conversation.close{/lang}</label>
						<label><input name="conversationAllowAdd" type="checkbox" value="1"{if $conversationAllowAdd} checked{/if}> {lang}wcf.acp.uzbot.notify.conversation.allowAdd{/lang}</label>
					</dd>
				</dl>
				
				<dl{if $errorField == 'conversationInvisible'} class="formError"{/if}>
					<dt><label for="conversationInvisible">{lang}wcf.acp.uzbot.notify.conversation.invisible{/lang}</label></dt>
					<dd>
						<textarea name="conversationInvisible" id="conversationInvisible" rows="1">{$conversationInvisible}</textarea>
						<small>{lang}wcf.acp.uzbot.notify.conversation.invisible.description{/lang}</small>
						
						{if $errorField == 'conversationInvisible'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.notify.conversation.invisible.error.{$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
			</div>
			
			<div class="section notifyEmailSettings">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.notify.email{/lang}</h2>
				</header>
				
				<dl>
					<dt>{lang}wcf.acp.uzbot.notify.email.emailPrivacy{/lang}</dt>
					<dd>
						<label><input name="emailPrivacy" type="checkbox" value="1"{if $emailPrivacy} checked{/if}> {lang}wcf.acp.uzbot.notify.email.emailPrivacy.enable{/lang}</label>
					</dd>
				</dl>
				
				<dl{if $errorField == 'emailCC'} class="formError"{/if}>
					<dt><label for="emailCC">{lang}wcf.acp.uzbot.notify.email.cc{/lang}</label></dt>
					<dd>
						<textarea name="emailCC" id="emailCC" rows="1">{$emailCC}</textarea>
						<small>{lang}wcf.acp.uzbot.notify.email.cc.description{/lang}</small>
						
						{if $errorField == 'emailCC'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.notify.email.cc.error.{$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
				
				<dl{if $errorField == 'emailBCC'} class="formError"{/if}>
					<dt><label for="emailBCC">{lang}wcf.acp.uzbot.notify.email.bcc{/lang}</label></dt>
					<dd>
						<textarea name="emailBCC" id="emailBCC" rows="1">{$emailBCC}</textarea>
						<small>{lang}wcf.acp.uzbot.notify.email.bcc.description{/lang}</small>
						
						{if $errorField == 'emailBCC'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.notify.email.bcc.error.{$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
				
				<dl{if $errorField == 'emailAttachmentFile'} class="formError"{/if}>
					<dt><label for="emailAttachmentFile">{lang}wcf.acp.uzbot.notify.email.file{/lang}</label></dt>
					<dd>
						<input type="text" id="emailAttachmentFile" name="emailAttachmentFile" value="{$emailAttachmentFile}" class="long" maxlength="255">
						<small>{lang}wcf.acp.uzbot.notify.email.file.description{/lang}</small>
						
						{if $errorField == 'emailAttachmentFile'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.notify.email.file.error.{$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
			</div>
			
			{event name='UzbotNotify'}
			
			<div class="section notifyReceiverSettings">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.notify.receiver{/lang} <span class="icon icon24 fa-question-circle-o jsUzbotHelp pointer" data-help-item="notify.receiver"></span></h2>
				</header>
				
				<dl{if $errorField == 'receiverAffected'} class="formError"{/if} id="receiverAffected">
					<dt>{lang}wcf.acp.uzbot.notify.receiver.affected{/lang}</dt>
					<dd>
						<label><input name="receiverAffected" type="checkbox" value="1"{if $receiverAffected} checked{/if}> {lang}wcf.acp.uzbot.notify.receiver.affected.use{/lang}</label>
					</dd>
					
					{if $errorField == 'receiverAffected'}
						<small class="innerError">
							{lang}wcf.acp.uzbot.notify.receiver.affected.error.{$errorType}{/lang}
						</small>
					{/if}
				</dl>
				
				<dl{if $errorField == 'receiverNames'} class="formError"{/if}>
					<dt><label for="receiverNames">{lang}wcf.acp.uzbot.notify.receiver.names{/lang}</label></dt>
					<dd>
						<textarea name="receiverNames" id="receiverNames" rows="1">{$receiverNames}</textarea>
						<small>{lang}wcf.acp.uzbot.notify.receiver.names.description{/lang}</small>
						
						{if $errorField == 'receiverNames'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.notify.receiver.names.error.{$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
				
				<dl{if $errorField == 'receiverGroupIDs'} class="formError"{/if}>
					<dt><label>{lang}wcf.acp.uzbot.notify.receiver.groupIDs{/lang}</label></dt>
					<dd>
						{htmlCheckboxes options=$receiverGroups name=receiverGroupIDs selected=$receiverGroupIDs}
						
						{if $errorField == 'receiverGroupIDs'}
							<small class="innerError">
								{lang}wcf.acp.uzbot.notify.receiver.groupIDs.error.{$errorType}{/lang}
							</small>
						{/if}
					</dd>
				</dl>
			</div>
			
			<div class="section notifyReceiverConditionSettings">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.notify.receiver.condition{/lang}</h2>
					<p class="sectionDescription">{lang}wcf.acp.uzbot.notify.receiver.condition.description{/lang}</p>
				</header>
				
				{include file='uzbotReceiverConditions'}
			</div>
			
			<div class="section" id="notifyLabelContainer">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.notify.label{/lang}</h2>
				</header>
				
				{if $labelGroups|count}
					{foreach from=$labelGroups item=labelGroup}
						{if $labelGroup|count}
							<dl>
								<dt><label>{$labelGroup->getTitle()}</label></dt>
								<dd>
									<ul class="labelList jsOnly" data-object-id="{@$labelGroup->groupID}">
										<li class="dropdown notifyLabelChooser" id="labelGroup{@$labelGroup->groupID}" data-group-id="{@$labelGroup->groupID}" data-force-selection="false">
											<div class="dropdownToggle" data-toggle="labelGroup{@$labelGroup->groupID}"><span class="badge label">{lang}wcf.label.none{/lang}</span></div>
											<div class="dropdownMenu">
												<ul class="scrollableDropdownMenu">
													{foreach from=$labelGroup item=label}
														<li data-label-id="{@$label->labelID}"><span><span class="badge label{if $label->getClassNames()} {@$label->getClassNames()}{/if}">{lang}{$label->label}{/lang}</span></span></li>
													{/foreach}
												</ul>
											</div>
										</li>
									</ul>
								</dd>
							</dl>
						{/if}
					{/foreach}
				{/if}
			</div>
			
			<div class="section" id="notifyTextContainer">
				<header class="sectionHeader">
					<h2 class="sectionTitle">{lang}wcf.acp.uzbot.notify.text{/lang}</h2>
				</header>
				
				{if !$isMultilingual}
					<div class="section">
						<!-- condense -->
						<dl{if $errorField == 'condense'} class="formError notifyCondense"{else} class="notifyCondense"{/if}>
							<dt><label for="condense0">{lang}wcf.acp.uzbot.notify.text.condense{/lang}</label> <span class="icon icon24 fa-question-circle-o jsUzbotHelp helpType pointer" data-help-item="condense.setting"></span></dt>
							<dd>
								<input type="text" id="condense0" name="condense[0]" value="{if !$condense[0]|empty}{$condense[0]}{/if}" class="long" maxlength="255">
								
								{if $errorField == 'condense'}
									<small class="innerError">
										{if $errorType == 'empty'}
											{lang}wcf.global.form.error.empty{/lang}
										{else}
											{lang}wcf.acp.uzbot.notify.text.condense.error.{$errorType}{/lang}
										{/if}
									</small>
								{/if}
							</dd>
						</dl>
						
						<!-- image -->
						{if $__wcf->session->getPermission('admin.content.cms.canUseMedia')}
							<dl{if $errorField == 'image'} class="formError notifyImage"{else} class="notifyImage"{/if}>
								<dt><label for="image">{lang}wcf.acp.article.image{/lang}</label></dt>
								<dd>
									<div id="imageDisplay" class="selectedImagePreview">
										{if $images[0]|isset && $images[0]->hasThumbnail('small')}
											{@$images[0]->getThumbnailTag('small')}
										{/if}
									</div>
									<p class="button jsMediaSelectButton" data-store="imageID0" data-display="imageDisplay">{lang}wcf.media.chooseImage{/lang}</p>
									<input type="hidden" name="imageID[0]" id="imageID0"{if $imageID[0]|isset} value="{@$imageID[0]}"{/if}>
									{if $errorField == 'image'}
										<small class="innerError">{lang}wcf.acp.article.image.error.{@$errorType}{/lang}</small>
									{/if}
								</dd>
							</dl>
						{elseif $action == 'edit' && $images[0]|isset && $images[0]->hasThumbnail('small')}
							<dl>
								<dt>{lang}wcf.acp.article.image{/lang}</dt>
								<dd>
									<div id="imageDisplay">{@$images[0]->getThumbnailTag('small')}</div>
								</dd>
							</dl>
						{/if}
						
						<!-- teaser image -->
						{if $__wcf->session->getPermission('admin.content.cms.canUseMedia')}
							<dl{if $errorField == 'teaserImage'} class="formError notifyTeaserImage"{else} class="notifyTeaserImage"{/if}>
								<dt><label for="teaserImage">{lang}wcf.acp.article.teaserImage{/lang}</label></dt>
								<dd>
									<div id="teaserImageDisplay" class="selectedImagePreview">
										{if $teaserImages[0]|isset && $teaserImages[0]->hasThumbnail('small')}
											{@$teaserImages[0]->getThumbnailTag('small')}
										{/if}
									</div>
									<p class="button jsMediaSelectButton" data-store="teaserImageID0" data-display="teaserImageDisplay">{lang}wcf.media.chooseImage{/lang}</p>
									<input type="hidden" name="teaserImageID[0]" id="teaserImageID0"{if $teaserImageID[0]|isset} value="{@$teaserImageID[0]}"{/if}>
									{if $errorField == 'teaserImage'}
										<small class="innerError">{lang}wcf.acp.article.image.error.{@$errorType}{/lang}</small>
									{/if}
								</dd>
							</dl>
						{elseif $action == 'edit' && $teaserImages[0]|isset && $teaserImages[0]->hasThumbnail('small')}
							<dl>
								<dt>{lang}wcf.acp.article.teaserImage{/lang}</dt>
								<dd>
									<div id="teaserImageDisplay">{@$teaserImages[0]->getThumbnailTag('small')}</div>
								</dd>
							</dl>
						{/if}
						
						<!-- subject -->
						<dl{if $errorField == 'subject'} class="formError notifySubject"{else} class="notifySubject"{/if}>
							<dt><label for="subject0">{lang}wcf.acp.uzbot.notify.text.subject{/lang}</label></dt>
							<dd>
								<input type="text" id="subject0" name="subject[0]" value="{if !$subject[0]|empty}{$subject[0]}{/if}" class="long" maxlength="255">
								
								{if $errorField == 'subject'}
									<small class="innerError">
										{if $errorType == 'empty'}
											{lang}wcf.global.form.error.empty{/lang}
										{else}
											{lang}wcf.acp.uzbot.notify.text.subject.error.{$errorType}{/lang}
										{/if}
									</small>
								{/if}
							</dd>
						</dl>
						
						{if MODULE_TAGGING}
							<dl class="jsOnly notifyTags">
								<dt><label for="tagSearchInput">{lang}wcf.tagging.tags{/lang}</label></dt>
								<dd>
									<input name="tagSearchInput" id="tagSearchInput" type="text" value="" class="long">
									<small>{lang}wcf.tagging.tags.description{/lang}</small>
								</dd>
							</dl>
							
							<script data-relocate="true">
								require(['WoltLabSuite/Core/Ui/ItemList'], function(UiItemList) {
									UiItemList.init(
										'tagSearchInput',
										[{if !$tags[0]|empty}{implode from=$tags[0] item=tag}'{$tag|encodeJS}'{/implode}{/if}],
										{
											ajax: {
												className: 'wcf\\data\\tag\\TagAction'
											},
											maxLength: {@TAGGING_MAX_TAG_LENGTH},
											submitFieldName: 'tags[0][]'
										}
									);
								});
							</script>
						{/if}
						
						<dl{if $errorField == 'teaser'} class="formError notifyTeaser"{else} class="notifyTeaser"{/if}>
							<dt><label for="teaser0">{lang}wcf.acp.uzbot.notify.text.teaser{/lang}</label></dt>
							<dd>
								<textarea name="teaser[0]" id="teaser0" rows="3">{if !$teaser[0]|empty}{$teaser[0]}{/if}</textarea>
								
								{if $errorField == 'teaser'}
									<small class="innerError">
										{if $errorType == 'empty'}
											{lang}wcf.global.form.error.empty{/lang}
										{else}
											{lang}wcf.acp.uzbot.notify.text.teaser.error.{$errorType}{/lang}
										{/if}
									</small>
								{/if}
							</dd>
						</dl>
						
						<dl{if $errorField == 'content'} class="formError notifyContent"{else} class="notifyContent"{/if}>
							<dt><label for="content0">{lang}wcf.acp.uzbot.notify.text.content{/lang}</label></dt>
							<dd>
								<textarea name="content[0]" id="content0" class="wysiwygTextarea" data-disable-media="1">{if !$content[0]|empty}{$content[0]}{/if}</textarea>
								{include file='wysiwyg' wysiwygSelector='content0'}
								{if $errorField == 'content'}
									<small class="innerError">
										{if $errorType == 'empty'}
											{lang}wcf.global.form.error.empty{/lang}
										{else}
											{lang}wcf.acp.uzbot.notify.text.content.error.{@$errorType}{/lang}
										{/if}
									</small>
								{/if}
							</dd>
						</dl>
					</div>
				{else}
					<div class="section tabMenuContainer">
						<nav class="tabMenu">
							<ul>
								{foreach from=$availableLanguages item=availableLanguage}
									{assign var='containerID' value='language'|concat:$availableLanguage->languageID}
									<li><a href="{@$__wcf->getAnchor($containerID)}">{$availableLanguage->languageName}</a></li>
								{/foreach}
							</ul>
						</nav>
						
						{foreach from=$availableLanguages item=availableLanguage}
							<div id="language{@$availableLanguage->languageID}" class="tabMenuContent">
								<div class="section">
									<dl{if $errorField == 'condense'|concat:$availableLanguage->languageID} class="formError notifyCondense"{else} class="notifyCondense"{/if}>
										<dt><label for="condense{@$availableLanguage->languageID}">{lang}wcf.acp.uzbot.notify.text.condense{/lang}</label> <span class="icon icon24 fa-question-circle-o jsUzbotHelp helpType pointer" data-help-item="condense.setting"></span></dt>
										<dd>
											<input type="text" id="condense{@$availableLanguage->languageID}" name="condense[{@$availableLanguage->languageID}]" value="{if !$condense[$availableLanguage->languageID]|empty}{$condense[$availableLanguage->languageID]}{/if}" class="long" maxlength="255">
											
											{if $errorField == 'condense'|concat:$availableLanguage->languageID}
												<small class="innerError">
													{if $errorType == 'empty'}
														{lang}wcf.global.form.error.empty{/lang}
													{else}
														{lang}wcf.acp.uzbot.notify.text.condense.error.{$errorType}{/lang}
													{/if}
												</small>
											{/if}
										</dd>
									</dl>
									
									{if $__wcf->session->getPermission('admin.content.cms.canUseMedia')}
										<dl{if $errorField == 'image'|concat:$availableLanguage->languageID} class="formError notifyImage"{else} class="notifyImage"{/if}>
											<dt><label for="image{@$availableLanguage->languageID}">{lang}wcf.acp.article.image{/lang}</label></dt>
											<dd>
												<div id="imageDisplay{@$availableLanguage->languageID}">
													{if $images[$availableLanguage->languageID]|isset && $images[$availableLanguage->languageID]->hasThumbnail('small')}
														{@$images[$availableLanguage->languageID]->getThumbnailTag('small')}
													{/if}
												</div>
												<p class="button jsMediaSelectButton" data-store="imageID{@$availableLanguage->languageID}" data-display="imageDisplay{@$availableLanguage->languageID}">{lang}wcf.media.chooseImage{/lang}</p>
												<input type="hidden" name="imageID[{@$availableLanguage->languageID}]" id="imageID{@$availableLanguage->languageID}"{if $imageID[$availableLanguage->languageID]|isset} value="{@$imageID[$availableLanguage->languageID]}"{/if}>
												{if $errorField == 'image'|concat:$availableLanguage->languageID}
													<small class="innerError">{lang}wcf.acp.article.image.error.{@$errorType}{/lang}</small>
												{/if}
											</dd>
										</dl>
									{elseif $action == 'edit' && $images[$availableLanguage->languageID]|isset && $images[$availableLanguage->languageID]->hasThumbnail('small')}
										<dl>
											<dt>{lang}wcf.acp.article.image{/lang}</dt>
											<dd>
												<div id="imageDisplay">{@$images[$availableLanguage->languageID]->getThumbnailTag('small')}</div>
											</dd>
										</dl>
									{/if}
									
									{if $__wcf->session->getPermission('admin.content.cms.canUseMedia')}
										<dl{if $errorField == 'image'|concat:$availableLanguage->languageID} class="formError notifyTeaserImage"{else} class="notifyTeaserImage"{/if}>
											<dt><label for="teaserImage{@$availableLanguage->languageID}">{lang}wcf.acp.article.teaserImage{/lang}</label></dt>
											<dd>
												<div id="teaserImageDisplay{@$availableLanguage->languageID}">
													{if $teaserImages[$availableLanguage->languageID]|isset && $teaserImages[$availableLanguage->languageID]->hasThumbnail('small')}
														{@$teaserImages[$availableLanguage->languageID]->getThumbnailTag('small')}
													{/if}
												</div>
												<p class="button jsMediaSelectButton" data-store="teaserImageID{@$availableLanguage->languageID}" data-display="teaserImageDisplay{@$availableLanguage->languageID}">{lang}wcf.media.chooseImage{/lang}</p>
												<input type="hidden" name="teaserImageID[{@$availableLanguage->languageID}]" id="teaserImageID{@$availableLanguage->languageID}"{if $teaserImageID[$availableLanguage->languageID]|isset} value="{@$teaserImageID[$availableLanguage->languageID]}"{/if}>
												{if $errorField == 'teaserImage'|concat:$availableLanguage->languageID}
													<small class="innerError">{lang}wcf.acp.article.image.error.{@$errorType}{/lang}</small>
												{/if}
											</dd>
										</dl>
									{elseif $action == 'edit' && $teaserImages[$availableLanguage->languageID]|isset && $teaserImages[$availableLanguage->languageID]->hasThumbnail('small')}
										<dl>
											<dt>{lang}wcf.acp.article.teaserImage{/lang}</dt>
											<dd>
												<div id="imageDisplay">{@$teaserImages[$availableLanguage->languageID]->getThumbnailTag('small')}</div>
											</dd>
										</dl>
									{/if}
									
									<dl{if $errorField == 'subject'|concat:$availableLanguage->languageID} class="formError notifySubject"{else} class="notifySubject"{/if}>
										<dt><label for="subject{@$availableLanguage->languageID}">{lang}wcf.acp.uzbot.notify.text.subject{/lang}</label></dt>
										<dd>
											<input type="text" id="subject{@$availableLanguage->languageID}" name="subject[{@$availableLanguage->languageID}]" value="{if !$subject[$availableLanguage->languageID]|empty}{$subject[$availableLanguage->languageID]}{/if}" class="long" maxlength="255">
											
											{if $errorField == 'subject'|concat:$availableLanguage->languageID}
												<small class="innerError">
													{if $errorType == 'empty'}
														{lang}wcf.global.form.error.empty{/lang}
													{else}
														{lang}wcf.acp.uzbot.notify.text.subject.error.{$errorType}{/lang}
													{/if}
												</small>
											{/if}
										</dd>
									</dl>
									
									{if MODULE_TAGGING}
										<dl class="jsOnly notifyTags">
											<dt><label for="tagSearchInput{@$availableLanguage->languageID}">{lang}wcf.tagging.tags{/lang}</label></dt>
											<dd>
												<input id="tagSearchInput{@$availableLanguage->languageID}" type="text" value="" class="long">
												<small>{lang}wcf.tagging.tags.description{/lang}</small>
											</dd>
										</dl>
										
										<script data-relocate="true">
											require(['WoltLabSuite/Core/Ui/ItemList'], function(UiItemList) {
												UiItemList.init(
													'tagSearchInput{@$availableLanguage->languageID}',
													[{if !$tags[$availableLanguage->languageID]|empty}{implode from=$tags[$availableLanguage->languageID] item=tag}'{$tag|encodeJS}'{/implode}{/if}],
													{
														ajax: {
															className: 'wcf\\data\\tag\\TagAction'
														},
														maxLength: {@TAGGING_MAX_TAG_LENGTH},
														submitFieldName: 'tags[{@$availableLanguage->languageID}][]'
													}
												);
											});
										</script>
									{/if}
									
									<dl{if $errorField == 'teaser'|concat:$availableLanguage->languageID} class="formError notifyTeaser"{else} class="notifyTeaser"{/if}>
										<dt><label for="teaser{@$availableLanguage->languageID}">{lang}wcf.acp.uzbot.notify.text.teaser{/lang}</label></dt>
										<dd>
											<textarea name="teaser[{@$availableLanguage->languageID}]" id="teaser{@$availableLanguage->languageID}" rows="3">{if !$teaser[$availableLanguage->languageID]|empty}{$teaser[$availableLanguage->languageID]}{/if}</textarea>
											
											{if $errorField == 'teaser'|concat:$availableLanguage->languageID}
												<small class="innerError">
													{if $errorType == 'empty'}
														{lang}wcf.global.form.error.empty{/lang}
													{else}
														{lang}wcf.acp.uzbot.notify.text.teaser.error.{$errorType}{/lang}
													{/if}
												</small>
											{/if}
										</dd>
									</dl>
									
									<dl{if $errorField == 'content'|concat:$availableLanguage->languageID} class="formError notifyContent"{else} class="notifyContent"{/if}>
										<dt><label for="content{@$availableLanguage->languageID}">{lang}wcf.acp.uzbot.notify.text.content{/lang}</label></dt>
										<dd>
											<textarea name="content[{@$availableLanguage->languageID}]" id="content{@$availableLanguage->languageID}" class="wysiwygTextarea" data-disable-media="1">{if !$content[$availableLanguage->languageID]|empty}{$content[$availableLanguage->languageID]}{/if}</textarea>
											{include file='wysiwyg' wysiwygSelector='content'|concat:$availableLanguage->languageID}
											{if $errorField == 'content'|concat:$availableLanguage->languageID}
												<small class="innerError">
													{if $errorType == 'empty'}
														{lang}wcf.global.form.error.empty{/lang}
													{else}
														{lang}wcf.acp.uzbot.notify.text.content.error.{@$errorType}{/lang}
													{/if}
												</small>
											{/if}
										</dd>
									</dl>
								</div>
							</div>
						{/foreach}
					</div>
				{/if}
			</div>
		</div>
	</div>
	
	<div class="formSubmit">
		<input type="submit" value="{lang}wcf.global.button.submit{/lang}" accesskey="s" />
		{csrfToken}
	</div>
</form>

<script data-relocate="true" src="{@$__wcf->getPath()}js/WCF.Label.js"></script>
<script data-relocate="true">
	$(function() {
		WCF.Language.addObject({
			'wcf.label.none': '{lang}wcf.label.none{/lang}'
		});
	//	new WCF.Label.Chooser({ {implode from=$notifyLabelIDs key=groupID item=labelID}{@$groupID}: {@$labelID}{/implode} }, '#formContainer');
		
	});
</script>

<script data-relocate="true" src="{@$__wcf->getPath()}/acp/js/UZBOT.ACP.js"></script>
<script data-relocate="true">
	$(function() {
		WCF.Language.addObject({
			'wcf.label.none': '{lang}wcf.label.none{/lang}'
		});
		new UZBOT.ACP.ActionLabelChooser({ {implode from=$actionLabelIDs key=groupID item=labelID}{@$groupID}: {@$labelID}{/implode} }, '#formContainer');
		new UZBOT.ACP.ConditionLabelChooser({ {implode from=$conditionLabelIDs key=groupID item=labelID}{@$groupID}: {@$labelID}{/implode} }, '#formContainer');
		new UZBOT.ACP.NotifyLabelChooser({ {implode from=$notifyLabelIDs key=groupID item=labelID}{@$groupID}: {@$labelID}{/implode} }, '#formContainer');
		
	});
</script>

<script data-relocate="true">
	$(function() {
		var $notifyID = $('#notifyID').change(function(event) {
			var $value = $(event.currentTarget).val();
			
			$('#notifyTextContainer, #notifyLabelContainer').hide();
			$('.notifyContent, .notifySenderName, .notifyLanguageID, .notifyImage, .notifyTeaserImage, .notifySubject, .notifyTags, .notifyTeaser').hide();
			$('.notifyArticleSettings, .notifyEmailSettings, .notifyCommentSettings, .notifyConversationSettings').hide();
			$('.notifyReceiverSettings, .notifyReceiverConditionSettings').hide();
			
			if ($value != 0) {
				$('#notifyTextContainer').show();
				$('.notifyContent, .notifySenderName, .notifyLanguageID').show();
			}
			if ($value == 1) {
				$('.notifyReceiverSettings, .notifyReceiverConditionSettings').show();
			}
			if ($value == 2) {
				$('.notifyEmailSettings, .notifySubject').show();
				$('.notifyReceiverSettings, .notifyReceiverConditionSettings').show();
			}
			if ($value == 3) {
				$('.notifyArticleSettings, .notifyImage, .notifyTeaserImage, .notifySubject, .notifyTeaser, .notifyTags').show();
				$('#notifyLabelContainer').show();
			}
			if ($value == 4) {
				$('.notifyCommentSettings').show();
				$('.notifyReceiverSettings, .notifyReceiverConditionSettings').show();
			}
			if ($value == 5) {
				$('.notifyConversationSettings, .notifySubject').show();
				$('.notifyReceiverSettings, .notifyReceiverConditionSettings').show();
			}
			
			{event name='UzbotNotifyJS'}
		});
		
		$notifyID.trigger('change');
		
		var $typeID = $('#typeID').change(function(event) {
			var value = $(event.currentTarget).val();
			var help = elBySel('.helpType');
			help.setAttribute('data-help-item' , 'help.type.'+value);
			
			$('.circularSetting, .uzbotUserBotConditions, .uzbotUserConditions, .feedreaderSetting, .groupAssignmentSetting').hide();
			$('.article_change, .article_new').hide();
			$('.system_update, .system_report, .system_error, .system_circular, .system_comment, .system_conversation, .system_contact, .system_statistics').hide();
			$('.user_birthday, .user_count, .user_creation, .user_groupChange, .user_likes, .user_setting, .user_warning, .inactiveSetting').hide();
			$('.user_ban, .user_unban').hide();
			$('.user_trophy').hide();
			$('.condenseSetting, .affectedSetting').hide();
			$('#receiverAffected, #actionLabelContainer, #conditionLabelContainer').hide();
			$('.notifyCondense').hide();
			
			if (value == 1) { $('.feedreaderSetting').show(); }
			if (value == 2) { $('.system_update').show(); }
			if (value == 3) { $('.system_error').show(); }
			if (value == 4) {
				$('.system_conversation, .condenseSetting, .uzbotUserBotConditions, .uzbotUserConditions').show();
				$('#receiverAffected').show();
				if ($('#condenseEnable').is(':checked')) { $('.notifyCondense').show(); }
			}
			
			if (value == 5) {
				$('.system_report, .affectedSetting').show();
				$('#receiverAffected').show();
			}
			
			if (value == 6) {
				$('.system_circular, .uzbotUserBotConditions, .uzbotUserConditions').show();
				$('#receiverAffected').show();
			}
			
			if (value == 7) {
				$('.system_comment, .condenseSetting, .uzbotUserBotConditions, .uzbotUserConditions').show();
				$('#receiverAffected').show();
				if ($('#condenseEnable').is(':checked')) { $('.notifyCondense').show(); }
			}
			
			if (value == 8) {
				$('.system_statistics').show();
			}
			
			if (value == 10) {
				$('.groupAssignmentSetting, .condenseSetting, .uzbotUserBotConditions, .uzbotUserConditions').show();
				$('#receiverAffected').show();
				if ($('#condenseEnable').is(':checked')) { $('.notifyCondense').show(); }
			}
			
			if (value == 11) {
				$('.user_creation').show();
				$('#receiverAffected').show();
			}
			
			if (value == 12) {
				$('.user_count').show();
				$('#receiverAffected').show();
			}
			
			if (value == 13) {
				$('.user_birthday, .condenseSetting, .uzbotUserBotConditions, .uzbotUserConditions').show();
				$('#receiverAffected').show();
				if ($('#condenseEnable').is(':checked')) { $('.notifyCondense').show(); }
			}
			
			if (value == 14) {
				$('.user_count, .condenseSetting, .uzbotUserBotConditions, .uzbotUserConditions').show();
				$('#receiverAffected').show();
				if ($('#condenseEnable').is(':checked')) { $('.notifyCondense').show(); }
			}
			
			if (value == 15) {
				$('.inactiveSetting, .condenseSetting, .uzbotUserBotConditions, .uzbotUserConditions').show();
				$('#receiverAffected').show();
				if ($('#condenseEnable').is(':checked')) { $('.notifyCondense').show(); }
			}
			
			if (value == 16) {
				$('.user_warning, .affectedSetting').show();
				$('#receiverAffected').show();
			}
			
			if (value == 17) {
				$('.user_setting').show();
				$('#receiverAffected').show();
			}
			
			if (value == 18) {
				$('.user_groupChange').show();
				$('#receiverAffected').show();
			}
			
			if (value == 19) {
				$('.user_likes, .user_count, .uzbotUserConditions').show();
				$('#receiverAffected').show();
			}
			
			if (value == 20) {
				$('.article_change').show();
				$('#receiverAffected').show();
			}
			
			if (value == 21) {
				$('.article_new').show();
				$('#receiverAffected').show();
			}
			
			if (value == 22) {
				$('.user_trophy').show();
				$('#receiverAffected, .user_count').show();
			}
			
			if (value == 23) {
				$('.system_contact').show();
				$('#receiverAffected').show();
			}
			
			if (value == 24) {
				$('.user_ban').show();
				$('#receiverAffected').show();
			}
			
			if (value == 26) {
				$('.user_unban').show();
				$('#receiverAffected').show();
			}
			
			{event name='UzbotTypeJS'}
			
		});
		$typeID.trigger('change');
		
		$('#condenseEnable').change(function (event) {
			if ($('#condenseEnable').is(':checked')) {
				$('.notifyCondense').show();
			}
			else {
				$('.notifyCondense').hide();
			}
		});
		$('#condenseEnable').change();
		
	});
</script>

{include file='footer'}
