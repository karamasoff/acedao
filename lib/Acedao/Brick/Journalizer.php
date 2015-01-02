<?php
namespace Voilab\Acedao\Brick;

trait Journalizer {

    /**
     * Get the name/id/whatever you want of the user who is manipulating the data
     * @return String
     */
    abstract public function getJournalizeUser();

    /**
     * Get list of the journalization fields
     * @return array
     */
    final public function getJournalizeFields() {
        return array('created_at', 'created_by', 'updated_at', 'updated_by');
    }
}