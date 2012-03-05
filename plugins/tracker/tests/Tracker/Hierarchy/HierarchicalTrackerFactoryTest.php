<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../../../include/Tracker/Hierarchy/Dao.class.php');
require_once(dirname(__FILE__).'/../../../include/Tracker/Hierarchy/HierarchicalTrackerFactory.class.php');

Mock::generate('Tracker_Hierarchy_Dao');

class HierarchicalTrackerFactoryTest extends UnitTestCase {
    
    function testGetWithChildren() {
        $tracker = aTracker()->withId(1)->build();
        
        $dao = new MockTracker_Hierarchy_Dao();
        $children_ids = TestHelper::arrayToDar(array('parent_id' => 1, 'child_id' => 2), 
                                               array('parent_id' => 1, 'child_id' => 3));
        $dao->setReturnValue('getChildren', $children_ids, array($tracker->getId()));
        
        
        $child1 = aTracker()->withId(2)->build();
        $child2 = aTracker()->withId(3)->build();
        $tracker_factory = new MockTrackerFactory();
        $tracker_factory->setReturnValue('getTrackerById', $child1, array(2));
        $tracker_factory->setReturnValue('getTrackerById', $child2, array(3));
        
        $factory = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);
        $hierarchical_tracker = $factory->getWithChildren($tracker);
        
        $children = $hierarchical_tracker->getChildren();
        $children = $this->assertChildEquals($children, $child1);
        $children = $this->assertChildEquals($children, $child2);
        $this->assertEqual(count($children), 0);
    }
    
    private function assertChildEquals($children, $tracker) {
        $child = array_shift($children);
        $this->assertEqual($child, $tracker);
        return $children;
    }
    
    public function testGetPossibleChildren() {
        $dao = new MockTracker_Hierarchy_Dao();
        
        $project_id = 100;
        $tracker    = aTracker()->withId(1)->withProjectId($project_id)->build();
        
        $possible_child_1 = aTracker()->withId(2)->build();
        $possible_child_2 = aTracker()->withId(3)->build();
        
        $project_trackers = array(
            1 => $tracker,
            2 => $possible_child_1,
            3 => $possible_child_2
        );
        
        $tracker_factory = new MockTrackerFactory();
        $tracker_factory->setReturnValue('getTrackersByGroupId', $project_trackers, array($project_id));
        
        $factory = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);
        
        $expected_possible_children = array($possible_child_1, $possible_child_2);
        $actual_possible_children   = $factory->getPossibleChildren($tracker);
        
        $this->assertEqual(2, count($actual_possible_children));
        $this->assertEqual($possible_child_1, $actual_possible_children[0]);
        $this->assertEqual($possible_child_2, $actual_possible_children[1]);
    }
}

?>
