<?php

defined('_JEXEC') or die('Restricted access');

$libpath = JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_breezingcommerce' . DS . 'classes' . DS . 'plugin' . DS;
require_once($libpath . 'CrBcAPaymentAdminPlugin.php');
require_once($libpath . 'CrBcPaymentAdminPlugin.php');

class CrBc_Plugins_Payment_Rave_Admin extends CrBcAPaymentAdminPlugin implements CrBcPaymentAdminPlugin
{
    public function  __construct() {
        
        require_once(JPATH_SITE.'/administrator/components/com_breezingcommerce/classes/CrBcPane.php');
        
        // always call the parent constructor and always call it _first_
        parent::__construct();
        
        // define the default table for built-in list/details view
        $this->table = '#__breezingcommerce_plugin_payment_rave';
    }
    
    // allow raw for the info through beforeStore overwrite
    public function beforeStore($data){
        
        $test = JRequest::getBool('staging_account', false);
        
        if(!$test){
            $data['staging_account'] = 0;
        } else {
            $data['staging_account'] = 1;
        }
        
        return $data;
        
    }
    
    // allow raw for the info_translation through afterStore
    public function afterStore($data){
        
        $data['info_translation'] = JRequest::getVar( 'info_translation', '', 'POST', 'STRING', JREQUEST_ALLOWRAW );
        
        if(isset($data['info_translation']) && $data['info_translation'] != ''){
            $fields = array();
            $fields['body'] = trim($data['info_translation']);
            CrBcHelpers::storeTranslation($fields, $data[$this->identity_column], 'plugin_payment_rave');
        }
    }

    /**
     * Called on render HTML
     */
    function display(){
        $this->setDetailsView(array('apply', 'cancel'));
    }

    /**
     * Overriden from CrBcAdminPlugin
     * @param array $toolbarItems
     */
    function setDetailsView($toolbarItems = array()){

        $this->setToolbar($toolbarItems);
        $this->template = 'details';

        $db = JFactory::getDBO();
 
        $db->setQuery("Select * From " . $this->table . " Order By `".$this->identity_column."` Desc Limit 1");
        $row = $db->loadObject();

        if(!($row instanceof stdClass)){
            $row = new stdClass();
            $id = $this->identity_column;
            $row->$id = 0;
            $row->staging_account = 0;
            $row->live_pk = '';
            $row->live_sk = '';
            $row->test_pk = '';
            $row->test_sk = '';
            $row->payment_form = '';
            $row->logo = '';
            $row->title = '';
            $row->desc = '';
            $row->country = '';
            $row->payment_method = '';
            $row->metaname = '';
            $row->metavalue = '';

        }
        
        $id = $this->identity_column;
        $this->assignRef('entity', $row);
    }

    function init($subject = null){
        // nothing yet
    }

    function getPaymentInfo(){
        return "Rave Info";
    }

    function getAfterPaymentInfo(){
        return JText::_('COM_BREEZINGCOMMERCE_RAVE_INFO_PAID');
    }

    public function getPluginDisplayName(){
        return JText::_('COM_BREEZINGCOMMERCE_RAVE');
    }
    
    public function getPluginDescription(){
        return JText::_('COM_BREEZINGCOMMERCE_RAVE_DESCRIPTION');
    }
}
