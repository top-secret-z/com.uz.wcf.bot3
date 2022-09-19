<?php
use wcf\system\database\table\DatabaseTableChangeProcessor;
use wcf\system\database\table\PartialDatabaseTable;
use wcf\system\database\table\column\TextDatabaseTableColumn;
use wcf\system\WCF;

$tables = [
		// add new column inactiveBanReason in wcf1_uzbot
		PartialDatabaseTable::create('wcf1_uzbot')
			->columns([
					TextDatabaseTableColumn::create('inactiveBanReason'),
			])
];

(new DatabaseTableChangeProcessor(
		$this->installation->getPackage(),
		$tables,
		WCF::getDB()->getEditor())
)->process();
