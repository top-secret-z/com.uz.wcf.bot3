/**
 * Provides the dialog with help text.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
define(['Ajax', 'Language', 'Ui/Dialog'], function(Ajax, Language, UiDialog) {
	"use strict";
	
	function UzbotAcpHelp() { this.init(); }
	
	UzbotAcpHelp.prototype = {
		init: function() {
			var buttons = elBySelAll('.jsUzbotHelp');
			for (var i = 0, length = buttons.length; i < length; i++) {
				buttons[i].addEventListener(WCF_CLICK_EVENT, this._click.bind(this));
			}
			
			this._helpItem = '';
		},
		
		_ajaxSetup: function() {
			return {
				data: {
					actionName:	'getHelp',
					className:	'wcf\\data\\uzbot\\UzbotAction'
				}
			};
		},
		
		_ajaxSuccess: function(data) {
			UiDialog.open(this, data.returnValues.template);
		},
		
		_dialogSetup: function() {
			return {
				id: 'getHelp',
				options: {
					title: Language.get('wcf.acp.uzbot.help')
				},
				source: null
			};
		},
		
		_click: function(event) {
			this._helpItem = elData(event.currentTarget, 'help-item');
			
			Ajax.api(this, {
				parameters: {
					helpItem: this._helpItem
				}
			});
		}
	};
	return UzbotAcpHelp;
});
