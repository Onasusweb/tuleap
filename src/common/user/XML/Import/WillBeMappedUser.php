<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
namespace User\XML\Import;

use PFUser;
use UserManager;
use Logger;

class WillBeMappedUser implements User, ReadyToBeImportedUser {

    /** @var string */
    private $username;

    /** @var PFUser */
    private $mapped_user;

    public function __construct(
        $username,
        PFUser $mapped_user
    ) {
        $this->username    = $username;
        $this->mapped_user = $mapped_user;
    }

    public function getUserName() {
        return $this->username;
    }

    public function getMappedUser() {
        return $this->mapped_user;
    }

    public function process(UserManager $user_manager, Logger $logger) {
        $logger->warn($this->username .' should be mapped. Not yet implemented');
    }
}