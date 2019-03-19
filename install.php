<?php

defined('_JEXEC') or die('Restricted access');

class CrBcInstallation extends CrBcInstaller {

    public $type = 'payment';
    public $name = 'rave';

        function install(){
            
            
            $tables = JFactory::getDBO()->getTableList();
            
            settype($tables, 'array');

            foreach ($tables as $table){

                if( $table == JFactory::getDBO()->getPrefix() . 'breezingcommerce_plugin_payment_rave' ){

                    return;
                }
            }
            
            $db = JFactory::getDBO();
            $db->setQuery("CREATE TABLE IF NOT EXISTS `#__breezingcommerce_plugin_payment_rave` (
            `identity` int(11) NOT NULL,
            `staging_account` tinyint(4) NOT NULL DEFAULT '0',
            `live_pk` varchar(255) NOT NULL,
            `live_sk` varchar(255) NOT NULL,
            `test_pk` varchar(255) NOT NULL,
            `test_sk` varchar(255) NOT NULL,
            `payment_form` varchar(255) NOT NULL DEFAULT 'modal',
            `logo` varchar(255) NOT NULL,
            `title` varchar(255) NOT NULL,
            `desc` varchar(255) NOT NULL,
            `metaname` varchar(255) NOT NULL,
            `metavalue` varchar(255) NOT NULL,
            `payment_method` varchar(255) NOT NULL
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8
          ");
            $db->query();
        
        $db->setQuery("ALTER TABLE `#__breezingcommerce_plugin_payment_rave`
        ADD PRIMARY KEY (`identity`)

      ");
      $db->query();

                      $db->setQuery("ALTER TABLE `#__breezingcommerce_plugin_payment_rave`
        MODIFY `identity` int(11) NOT NULL AUTO_INCREMENT
      ");
              $db->query();
    }

}