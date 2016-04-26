<?php
/**
 * Copyright (c) Enalean SAS, 2016. All Rights Reserved.
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

namespace Tuleap\TeamforgeCompatGit\Plugin;

class PluginDescriptor extends \PluginDescriptor
{

    public function __construct()
    {
        parent::__construct(
            $GLOBALS['Language']->getText('plugin_teamforge_compat_git', 'descriptor_name'),
            false,
            $GLOBALS['Language']->getText('plugin_teamforge_compat_git', 'descriptor_description')
        );

        $this->setVersionFromFile(TEAMFORGE_COMPAT_GIT_BASE_DIR.'/VERSION');
    }
}