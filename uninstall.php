<?php

defined('_JEXEC') or die('Restricted access');

class CrBcUninstallation extends CrBcUninstaller {

    public $type = 'payment';
    public $name = 'rave';

    function uninstall(){
        $db = JFactory::getDBO();
        $db->setQuery("DROP TABLE IF EXISTS `#__breezingcommerce_plugin_payment_rave`");
        $db->query();
    }

}