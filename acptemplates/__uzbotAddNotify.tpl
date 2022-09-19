<div class="section">
	<header class="sectionHeader">
		<h2 class="sectionTitle">{lang}wcf.acp.uzbot.notify{/lang}</h2>
		<p class="sectionDescription">{lang}wcf.acp.uzbot.notify.description{/lang}</p>
	</header>
	
	<!-- notifyID -->
	<dl{if $errorField == 'notifyID'} class="formError"{/if}>
		<dt><label for="notifyID">{lang}wcf.acp.uzbot.notifyID{/lang}</label></dt>
		<dd>
			<select name="notifyID" id="notifyID">
			<	option value="0">{lang}wcf.global.noSelection{/lang}</option>
				{foreach from=$availableNotifies item=notify}
					<option value="{@$notify->notifyID}"{if $notify->notifyID == $notifyID} selected="selected"{/if}>{$notify->getTitle()}</option>
				{/foreach}
			</select>
			
			{if $errorField == 'notifyID'}
				<small class="innerError">
					{lang}wcf.acp.uzbot.notifyID.error.{@$errorType}{/lang}
				</small>
			{/if}
		</dd>
	</dl>
</div>

<div class="section" id="notifyTextContainer>
	<header class="sectionHeader">
		<h2 class="sectionTitle">{lang}wcf.acp.uzbot.notify{/lang}</h2>
		<p class="sectionDescription">{lang}wcf.acp.uzbot.notify.description{/lang}</p>
	</header>
	
	<!-- content -->
	{if !$isMultilingual}
		<div class="section">
			<!-- subject -->
			<dl{if $errorField == 'subject'} class="formError"{/if}>
				<dt><label for="subject0">{lang}wcf.acp.compulsory.subject{/lang}</label></dt>
				<dd>
					<input type="text" id="subject0" name="subject[0]" value="{if !$subject[0]|empty}{$subject[0]}{/if}" class="long" maxlength="255">
					<small>{lang}wcf.acp.compulsory.subject.description{/lang}</small>
					
					{if $errorField == 'subject'}
						<small class="innerError">
							{if $errorType == 'empty'}
								{lang}wcf.global.form.error.empty{/lang}
							{else}
								{lang}wcf.acp.compulsory.subject.error.{$errorType}{/lang}
							{/if}
						</small>
					{/if}
				</dd>
			</dl>
			
			<dl{if $errorField == 'teaser'} class="formError"{/if}>
				<dt><label for="teaser0">{lang}wcf.acp.compulsory.teaser{/lang}</label></dt>
				<dd>
					<input type="text" id="teaser0" name="teaser[0]" value="{if !$teaser[0]|empty}{$teaser[0]}{/if}" class="long" maxlength="255">
					<small>{lang}wcf.acp.compulsory.teaser.description{/lang}</small>
					
					{if $errorField == 'teaser'}
						<small class="innerError">
							{if $errorType == 'empty'}
								{lang}wcf.global.form.error.empty{/lang}
							{else}
								{lang}wcf.acp.compulsory.teaser.error.{$errorType}{/lang}
							{/if}
						</small>
					{/if}
				</dd>
			</dl>
			
			<dl{if $errorField == 'content'} class="formError"{/if}>
				<dt><label for="content0">{lang}wcf.acp.compulsory.content{/lang}</label></dt>
				<dd>
					<textarea name="content[0]" id="content0" class="wysiwygTextarea" data-autosave="com.uz.wcf.compulsory{$action|ucfirst}-{if $action == 'edit'}{@$compulsoryID}{else}0{/if}-0">{if !$content[0]|empty}{$content[0]}{/if}</textarea>
					{include file='wysiwyg' wysiwygSelector='content0'}
					{if $errorField == 'content'}
						<small class="innerError">
							{if $errorType == 'empty'}
								{lang}wcf.global.form.error.empty{/lang}
							{else}
								{lang}wcf.acp.compulsory.content.error.{@$errorType}{/lang}
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
						<dl{if $errorField == 'subject'|concat:$availableLanguage->languageID} class="formError"{/if}>
							<dt><label for="subject{@$availableLanguage->languageID}">{lang}wcf.acp.compulsory.subject{/lang}</label></dt>
							<dd>
								<input type="text" id="subject{@$availableLanguage->languageID}" name="subject[{@$availableLanguage->languageID}]" value="{if !$subject[$availableLanguage->languageID]|empty}{$subject[$availableLanguage->languageID]}{/if}" class="long" maxlength="255">
								<small>{lang}wcf.acp.compulsory.subject.description{/lang}</small>
								{if $errorField == 'subject'|concat:$availableLanguage->languageID}
									<small class="innerError">
										{if $errorType == 'empty'}
											{lang}wcf.global.form.error.empty{/lang}
										{else}
											{lang}wcf.acp.compulsory.subject.error.{$errorType}{/lang}
										{/if}
									</small>
								{/if}
							</dd>
						</dl>
						
						<dl{if $errorField == 'teaser'|concat:$availableLanguage->languageID} class="formError"{/if}>
							<dt><label for="teaser{@$availableLanguage->languageID}">{lang}wcf.acp.compulsory.teaser{/lang}</label></dt>
							<dd>
								<input type="text" id="teaser{@$availableLanguage->languageID}" name="teaser[{@$availableLanguage->languageID}]" value="{if !$teaser[$availableLanguage->languageID]|empty}{$teaser[$availableLanguage->languageID]}{/if}" class="long" maxlength="255">
								<small>{lang}wcf.acp.compulsory.teaser.description{/lang}</small>
								{if $errorField == 'teaser'|concat:$availableLanguage->languageID}
									<small class="innerError">
										{if $errorType == 'empty'}
											{lang}wcf.global.form.error.empty{/lang}
										{else}
											{lang}wcf.acp.compulsory.teaser.error.{$errorType}{/lang}
										{/if}
									</small>
								{/if}
							</dd>
						</dl>
						
						<dl{if $errorField == 'content'|concat:$availableLanguage->languageID} class="formError"{/if}>
							<dt><label for="content{@$availableLanguage->languageID}">{lang}wcf.acp.compulsory.content{/lang}</label></dt>
							<dd>
								<textarea name="content[{@$availableLanguage->languageID}]" id="content{@$availableLanguage->languageID}" class="wysiwygTextarea" data-autosave="com.uz.wcf.compulsory{$action|ucfirst}-{if $action == 'edit'}{@$compulsoryID}{else}0{/if}-{@$availableLanguage->languageID}">{if !$content[$availableLanguage->languageID]|empty}{$content[$availableLanguage->languageID]}{/if}</textarea>
								{include file='wysiwyg' wysiwygSelector='content'|concat:$availableLanguage->languageID}
								{if $errorField == 'content'|concat:$availableLanguage->languageID}
									<small class="innerError">
										{if $errorType == 'empty'}
											{lang}wcf.global.form.error.empty{/lang}
										{else}
											{lang}wcf.acp.compulsory.content.error.{@$errorType}{/lang}
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
