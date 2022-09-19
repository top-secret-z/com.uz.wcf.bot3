<?php
namespace wcf\acp\page;
use wcf\data\uzbot\log\UzbotLogList;
use wcf\page\SortablePage;
use wcf\system\WCF;

/**
 * Shows the Community Bot log list page.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotLogListPage extends SortablePage {
	/**
	 * @inheritDoc
	 */
	public $activeMenuItem = 'wcf.acp.menu.link.uzbot.log.list';
	
	/**
	 * @inheritDoc
	 */
	public $neededPermissions = ['admin.uzbot.canManageUzbot'];
	
	/**
	 * @inheritDoc
	 */
	public $neededModules = ['MODULE_UZBOT'];
	
	/**
	 * number of items shown per page
	 */
	public $itemsPerPage = 20;
	
	/**
	 * @inheritDoc
	 */
	public $defaultSortField = 'logID';
	
	/**
	 * @inheritDoc
	 */
	public $defaultSortOrder = 'DESC';
	
	/**
	 * @inheritDoc
	 */
	public $validSortFields = ['logID', 'time', 'botTitle', 'typeDes', 'notifyDes', 'status', 'count'];
	
	/**
	 * @inheritDoc
	 */
	public $objectListClassName = UzbotLogList::class;
	
	// filter data
	public $availableBots = [];
	public $botName = '';
	public $availableActions = [];
	public $botAction = '';
	public $availableStatus = [];
	public $botStatus = '';
	public $availableNotifies = [];
	public $botNotify = '';
	public $availableTestModus = [];
	public $botTestModus = '';
	
	/**
	 * @inheritDoc
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (!empty($_REQUEST['botAction'])) $this->botAction = $_REQUEST['botAction'];
		if (!empty($_REQUEST['botName'])) $this->botName = $_REQUEST['botName'];
		if (!empty($_REQUEST['botStatus'])) $this->botStatus = $_REQUEST['botStatus'];
		if (!empty($_REQUEST['botNotify'])) $this->botNotify = $_REQUEST['botNotify'];
		if (!empty($_REQUEST['botTestModus'])) $this->botTestModus = $_REQUEST['botTestModus'];
	}
	
	/**
	 * @inheritDoc
	 */
	protected function initObjectList() {
		parent::initObjectList();
		
		// get data
		$this->availableActions = $this->objectList->getBotActions();
		$this->availableBots = $this->objectList->getBotNames();
		$this->availableStatus = $this->objectList->getBotStatus();
		$this->availableNotifies = $this->objectList->getBotNotifies();
		
		$this->availableTestModus[WCF::getLanguage()->get('wcf.acp.uzbot.yes')] = WCF::getLanguage()->get('wcf.acp.uzbot.yes');
		$this->availableTestModus[WCF::getLanguage()->get('wcf.acp.uzbot.no')] = WCF::getLanguage()->get('wcf.acp.uzbot.no');
		
		// filter
		if (!empty($this->botAction)) {
			$this->objectList->getConditionBuilder()->add('typeDes LIKE ?', [$this->botAction]);
		}
		if (!empty($this->botName)) {
			$this->objectList->getConditionBuilder()->add('botTitle LIKE ?', [$this->botName]);
		}
		if (!empty($this->botStatus)) {
			$status = 0;
			if ($this->botStatus == WCF::getLanguage()->get('wcf.acp.uzbot.log.warning')) $status = 1;
			else if ($this->botStatus == WCF::getLanguage()->get('wcf.acp.uzbot.log.error')) $status = 2;
			
			$this->objectList->getConditionBuilder()->add('status = ?', [$status]);
		}
		if (!empty($this->botNotify)) {
			$this->objectList->getConditionBuilder()->add('notifyDes LIKE ?', [$this->botNotify]);
		}
		if (!empty($this->botTestModus)) {
			$modus = 0;
			if ($this->botTestModus == WCF::getLanguage()->get('wcf.acp.uzbot.yes')) $modus = 1;
			
			$this->objectList->getConditionBuilder()->add('testMode = ?', [$modus]);
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign([
				'botAction' => $this->botAction,
				'botName' => $this->botName,
				'botStatus' => $this->botStatus,
				'botNotify' => $this->botNotify,
				'botTestModus' => $this->botTestModus,
				'availableActions' => $this->availableActions,
				'availableBots' => $this->availableBots,
				'availableStatus' => $this->availableStatus,
				'availableNotifies' => $this->availableNotifies,
				'availableTestModus' => $this->availableTestModus
		]);
	}
}
