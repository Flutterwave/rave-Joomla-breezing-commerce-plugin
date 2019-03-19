<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

class CrBc_Plugins_Payment_Rave extends JTable
{
    /**
     * Primary Key
     *
     * @var int
     */
    public $identity = null;

    /**
     * @var string
     */
    public $info = null;

    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function __construct($table) {
        parent::__construct($table, 'identity', JFactory::getDBO());
    }
}

