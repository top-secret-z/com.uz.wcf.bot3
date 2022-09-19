/**
 * Clears the Bot log.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
define(['Ajax', 'Language', 'Ui/Confirmation'], function(Ajax, Language, UiConfirmation) {
	"use strict";
	
	function UzbotAcpLogClear() { this.init(); }
	
	UzbotAcpLogClear.prototype = {
		init: function() {
			var buttons = elBySelAll('.jsUzbotLogClear');
			for (var i = 0, length = buttons.length; i < length; i++) {
				buttons[i].addEventListener(WCF_CLICK_EVENT, this._click.bind(this));
			}
		},
		
		_click: function(event) {
			UiConfirmation.show({
				confirm: function() {
					Ajax.apiOnce({
						data: {
							actionName: 'clearAll',
							className: 'wcf\\data\\uzbot\\log\\UzbotLogAction'
						},
						success: function() {
							window.location.reload();
						}
					});
				},
				message: Language.get('wcf.acp.uzbot.log.clear.confirm')
			});	
		}
	};
	return UzbotAcpLogClear;
});
