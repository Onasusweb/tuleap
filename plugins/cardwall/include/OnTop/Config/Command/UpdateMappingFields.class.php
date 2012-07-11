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

require_once CARDWALL_BASE_DIR .'/OnTop/Config/Command.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/ColumnDao.class.php';

/**
 * Update a column for a cardwall on top of a tracker
 */
class Cardwall_OnTop_Config_Command_UpdateMappingFields extends Cardwall_OnTop_Config_Command {

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldDao
     */
    private $dao;

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldValueDao
     */
    private $value_dao;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var array
     */
    private $existing_mappings;

    public function __construct(
        Tracker $tracker,
        Cardwall_OnTop_ColumnMappingFieldDao $dao,
        Cardwall_OnTop_ColumnMappingFieldValueDao $value_dao,
        TrackerFactory $tracker_factory,
        Tracker_FormElementFactory $form_element_factory,
        array $existing_mappings) {
        parent::__construct($tracker);
        $this->dao                  = $dao;
        $this->value_dao            = $value_dao;
        $this->tracker_factory      = $tracker_factory;
        $this->form_element_factory = $form_element_factory;
        $this->existing_mappings    = $existing_mappings;
    }

    /**
     * @see Cardwall_OnTop_Config_Command::execute()
     */
    public function execute(Codendi_Request $request) {
        if (!is_array($request->get('mapping_field'))) return;
        $mapping_fields = $this->getMappingFields();
        foreach ($request->get('mapping_field') as $mapping_tracker_id => $mapping_tracker_info) {
            if (!isset($mapping_tracker_info['field'])) continue;
            $field_id = (int)$mapping_tracker_info['field'];
            $mapping_tracker = $this->tracker_factory->getTrackerById($mapping_tracker_id);
            $field           = $this->form_element_factory->getFieldById($field_id);
            $this->save($mapping_tracker_info, $mapping_fields, $mapping_tracker, $field);
        }
    }

    /**
     * @return array
     */
    private function getMappingFields() {
        $mapping_fields = array();
        $mapping_fields_rows = $this->dao->searchMappingFields($this->tracker->getId());
        foreach ($mapping_fields_rows as $row) {
            $mapping_fields[$row['tracker_id']] = $row['field_id'];
        }
        return $mapping_fields;
    }

    /**
     * @return void
     */
    private function save(array $mapping_tracker_info, array $mapping_fields, Tracker $mapping_tracker = null, Tracker_FormElement $field = null) {
        if ($this->canSaveNewField($mapping_fields, $mapping_tracker, $field)) {
            if ($this->fieldHasChanged($mapping_fields, $mapping_tracker, $field)) {
                $this->saveFieldMapping($mapping_tracker, $field);
            } else {
                $this->saveValuesMapping($mapping_tracker_info, $mapping_tracker, $field);
            }
        }
    }

    private function saveFieldMapping(Tracker $mapping_tracker, Tracker_FormElement $field) {
        if ($this->dao->save($this->tracker->getId(), $mapping_tracker->getId(), $field->getId())) {
            $this->value_dao->delete($this->tracker->getId(), $mapping_tracker->getId());
            $GLOBALS['Response']->addFeedback('info', 'Mapping on '. $mapping_tracker->getName() .' changed to '. $field->getLabel());
        }
    }

    private function saveValuesMapping(array $mapping_tracker_info, Tracker $mapping_tracker, Tracker_FormElement $field) {
        if (empty($mapping_tracker_info['values']) || !is_array($mapping_tracker_info['values'])) return;
        $nb_changes = 0;
        foreach ($mapping_tracker_info['values'] as $column_id => $values) {
            if ($this->mappingChanged($mapping_tracker, $column_id, $values)) {
                $this->value_dao->deleteAllFieldValues($this->tracker->getId(), $mapping_tracker->getId(), $field->getId(), $column_id);
                foreach ($values as $value_id) {
                    $nb_changes += $this->value_dao->save($this->tracker->getId(), $mapping_tracker->getId(), $field->getId(), (int)$value_id, $column_id);
                }
            }
        }
        $GLOBALS['Response']->addFeedback('info', $nb_changes.' mapping values changed for "'. $field->getLabel().'"');
    }

    private function mappingChanged(Tracker $mapping_tracker, $column_id, array $values) {
        $no_update_needed = true;
        if (isset($this->existing_mappings[$mapping_tracker->getId()])) {
            $value_mappings = $this->existing_mappings[$mapping_tracker->getId()]->getValueMappings();
            $already_processed = array();
            foreach ($value_mappings as $value_id => $value_mapping) {
                $already_processed[$value_id] = true;
                if ($value_mapping->getColumnId() == $column_id && in_array($value_id, $values)) {
                    $no_update_needed = $no_update_needed & true;
                } else {
                    $no_update_needed = $no_update_needed & false;
                }
            }
            // New values not already mapped;
            foreach ($values as $value_id) {
                if (!isset($already_processed[$value_id])) {
                    $no_update_needed = false;
                }
            }
        } else {
            $no_update_needed = false;
        }
        return !$no_update_needed;
    }
    /**
     * @return bool
     */
    private function canSaveNewField(array $mapping_fields, Tracker $mapping_tracker = null, Tracker_FormElement $field = null) {
        return $mapping_tracker && $field && $field->getTracker() == $mapping_tracker;
    }

    /**
     * @return bool
     */
    private function fieldHasChanged(array $mapping_fields, Tracker $mapping_tracker = null, Tracker_FormElement $field = null) {
        return !isset($mapping_fields[$mapping_tracker->getId()]) || $mapping_fields[$mapping_tracker->getId()] != $field->getId();
    }
}
?>
