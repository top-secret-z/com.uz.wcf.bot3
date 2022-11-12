<?php

/*
 * Copyright by Udo Zaydowicz.
 * Modified by SoftCreatR.dev.
 *
 * License: http://opensource.org/licenses/lgpl-license.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
namespace wcf\data\uzbot\log;

use wcf\data\DatabaseObjectEditor;
use wcf\system\language\LanguageFactory;

/**
 * Provides functions to edit Bot Log entries.
 */
class UzbotLogEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = UzbotLog::class;

    /**
     * @inheritDoc
     */
    public static function create(array $data = [])
    {
        // get default language
        $language = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
        $bot = $data['bot'];

        // optional packages might still deliver implode data for test mode
        $additional = 'wcf.acp.uzbot.error.none';
        if (isset($data['additionalData'])) {
            $additional = $data['additionalData'];

            // 2 are used in old versions
            if (\substr_count($additional, '(|)') >= 2) {
                $additional = \serialize(\explode('(|)', $additional));
            }
        }

        $parameters = [
            'time' => TIME_NOW,
            'botID' => $bot->botID,
            'botTitle' => $language->get($bot->botTitle),
            'typeID' => $bot->typeID,
            'typeDes' => $language->get($bot->typeDes),
            'notifyDes' => $language->get($bot->notifyDes),
            'status' => $data['status'] ?? 0,
            'testMode' => $data['testMode'] ?? 0,
            'count' => $data['count'] ?? 0,
            'additionalData' => $additional,
        ];

        parent::create($parameters);
    }
}
