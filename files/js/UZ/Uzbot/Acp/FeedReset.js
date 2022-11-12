/**
 * Resets a feed.
 * 
 * @author        2014-2022 Zaydowicz
 * @license        GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package        com.uz.wcf.bot3
 */
define(['Ajax', 'Language', 'Ui/Confirmation', 'Ui/Notification'], function(Ajax, Language, UiConfirmation, UiNotification) {
    "use strict";

    function UzbotAcpFeedReset() { this.init(); }

    UzbotAcpFeedReset.prototype = {
        init: function() {
            var button = elBySel('.jsUzbotFeedReset');
            button.addEventListener(WCF_CLICK_EVENT, this._click.bind(this));
        },

        _click: function(event) {
            var clicked = event.currentTarget;

            UiConfirmation.show({
                confirm: function() {
                    Ajax.apiOnce({
                        data: {
                            actionName: 'feedReset',
                            className: 'wcf\\data\\uzbot\\UzbotAction',
                            parameters: {
                                objectID: elData(clicked, 'object-id')
                            }
                        },
                        success: function() {
                            UiNotification.show();
                        }
                    });
                },
                message: Language.get('wcf.acp.uzbot.feedreader.reset.confirm')
            });    
        }
    };
    return UzbotAcpFeedReset;
});
