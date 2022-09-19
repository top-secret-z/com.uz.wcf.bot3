{include file='header' pageTitle='wcf.acp.uzbot.import.user'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}wcf.acp.uzbot.import.export.user{/lang}</h1>
	</div>
</header>

{include file='formError'}

{if $success|isset}
	<p class="success">{lang}wcf.acp.uzbot.import.user.success{/lang}</p>
{/if}

<form method="post" action="{link controller='UzbotImportUser'}{/link}" enctype="multipart/form-data">
	<div class="section">
		<header class="sectionHeader">
			<h2 class="sectionTitle">{lang}wcf.acp.uzbot.import.user{/lang}</h2>
		</header>
		
		<dl{if $errorField == 'uzbotImportUser'} class="formError"{/if}>
			<dt><label for="uzbotImportUser">{lang}wcf.acp.uzbot.import.user.upload{/lang}</label></dt>
			<dd>
				<input type="file" id="uzbotImportUser" name="uzbotImportUser" value="" />
				{if $errorField == 'uzbotImportUser'}
					<small class="innerError">
						{if $errorType == 'empty'}
							{lang}wcf.global.form.error.empty{/lang}
						{else}
							{lang}wcf.acp.uzbot.import.user.error.{@$errorType}{/lang}
						{/if}
					</small>
				{/if}
				<small>{lang}wcf.acp.uzbot.import.user.upload.description{/lang}</small>
			</dd>
		</dl>
	</div>
	
	<div class="formSubmit">
		<input type="submit" name="submitButton" value="{lang}wcf.global.button.submit{/lang}" accesskey="s" />
		{csrfToken}
	</div>
</form>

<div class="section">
	<header class="sectionHeader">
		<h2 class="sectionTitle">{lang}wcf.acp.uzbot.export.user{/lang}</h2>
	</header>
	<dl id="uzbotExportUserDiv">
		<dt><label>{lang}wcf.acp.uzbot.export.user.download{/lang}</label></dt>
		<dd>
			<p><a href="{link controller='UzbotExportUser'}{/link}" id="uzbotExportUser" class="button">{lang}wcf.acp.uzbot.export.user{/lang}</a></p>
			<small>{lang}wcf.acp.uzbot.export.user.download.description{/lang}</small>
		</dd>
	</dl>
</div>

{include file='footer'}
