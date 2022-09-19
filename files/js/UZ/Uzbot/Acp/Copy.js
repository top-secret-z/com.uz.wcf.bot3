/**
 * Copies a bot.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
define(['Ajax', 'Language', 'Ui/Confirmation', 'Ui/Notification'], function(Ajax, Language, UiConfirmation, UiNotification) {
	"use strict";
	
	function UzbotAcpCopy() { this.init(); }
	
	UzbotAcpCopy.prototype = {
		init: function() {
			var button = elBySel('.jsButtonBotCopy');
			
			button.addEventListener(WCF_CLICK_EVENT, this._click.bind(this));
		},
		
		_click: function(event) {
			event.preventDefault();
			var objectID = ~~elData(event.currentTarget, 'object-id');
			
			UiConfirmation.show({
				confirm: function() {
					Ajax.apiOnce({
						data: {
							actionName: 'copy',
							className: 'wcf\\data\\uzbot\\UzbotAction',
							parameters: {
								objectID: objectID
							}
						},
						success: function(data) {
							UiNotification.show();
							window.location = data.returnValues.redirectURL;
						}
					});
				},
				message: Language.get('wcf.acp.uzbot.copy.confirm')
			});	
		}
	};
	return UzbotAcpCopy;
});
