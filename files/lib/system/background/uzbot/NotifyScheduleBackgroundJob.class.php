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
namespace wcf\system\background\uzbot;

use wcf\system\background\job\AbstractBackgroundJob;

/**
 * Schedules notifications for a bot.
 */
class NotifyScheduleBackgroundJob extends AbstractBackgroundJob
{
    /**
     * data to send
     */
    protected $data;

    /**
     * Creates the job.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Notifies will be sent with an increasing timeout between the tries.
     */
    public function retryAfter()
    {
        switch ($this->getFailures()) {
            case 1:
                return 5 * 60;
            case 2:
                return 10 * 60;
            case 3:
                return 20 * 60;
        }
    }

    /**
     * @inheritDoc
     */
    public function perform()
    {
        $name = '\wcf\system\background\uzbot\NotifyScheduler';
        $name = new $name;

        try {
            $name->schedule($this->data);
        } catch (PermanentFailure $e) {
            \wcf\functions\exception\logThrowable($e);
        }
    }
}
