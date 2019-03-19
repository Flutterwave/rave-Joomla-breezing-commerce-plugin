<?php

defined('_JEXEC') or die('Restricted access');

$libpath = JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_breezingcommerce' . DS . 'classes' . DS . 'plugin' . DS;
require_once($libpath . 'CrBcAPaymentSitePlugin.php');
require_once($libpath . 'CrBcPaymentSitePlugin.php');

class CrBc_Plugins_Payment_Rave_Site extends CrBcAPaymentSitePlugin implements CrBcPaymentSitePlugin
{
    private $tx = '';

    function __construct()
    {
        // always call the parent constructor and always call it _first_
        parent::__construct();
        // define the default table for built-in list/details view
        $this->table = '#__breezingcommerce_plugin_payment_rave';
        $this->requeryCount = 0;
    }

    /**
     * Will return the tx id and is called right after a successfull and verified payment
     * through $this->verifyPayment()
     * 
     * @return mixed
     */
    function getPaymentTransactionId()
    {

        return $this->tx;
    }

    function requery($rave, $reference)
    {
        $apiLink = 'https://api.ravepay.co/';
        $secretKey = $rave->live_sk;
        if ($rave->staging_account == 1) {
            $apiLink = 'https://ravesandboxapi.flutterwave.com/';
            $secretKey = $rave->test_sk;
        }

        $txref = $reference;

        $this->requeryCount++;
        $data = array(
            'txref' => $txref,
            'SECKEY' => $secretKey,
            'last_attempt' => '1'
	        // 'only_successful' => '1'
        );
	    // make request to endpoint.
        $data_string = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiLink . 'flwv3-pug/getpaidx/api/v2/verify');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($ch);
        $resp = json_decode($response, false);
        $result = ['result' => 'failed'];
        if ($resp && $resp->status === "success") {
            if ($resp && $resp->data && $resp->data->status === "successful") {
                $result = ['result' => 'success', 'amount' => $resp->data->amount, 'currency' => $resp->data->currency, 'response' => $resp->data];
            } elseif ($resp && $resp->data && $resp->data->status === "failed") {
                $result = ['result' => 'failed'];
            } else {
                if ($this->requeryCount > 4) {
                    $result = ['result' => 'failed'];
                } else {
                    sleep(3);
                    $this->requery($rave, $reference);
                }
            }
        } else {
            if ($this->requeryCount > 4) {
                $result = ['result' => 'failed'];
            } else {
                sleep(3);
                $this->requery($rave, $reference);
            }
        }

        return $result;
    }



    function verifyPayment(CrBcCart $_cart, stdClass $order)
    {

        $db = JFactory::getDBO();

        $db->setQuery("Select * From " . $this->table . " Order By `" . $this->identity_column . "` Desc Limit 1");
        $rave = $db->loadObject();

        if (!($rave instanceof stdClass)) {
            throw new Exception('No rave payment setup found, please create one first in Admin => BreezingCommerce => Plugins => rave');
        }

        if ($order === null || $_cart === null) {
            throw new Exception('Invalid order object');
        }

        $_cart_items = $_cart->getItems(true);

        if (count($_cart_items) == 0) {
            throw new Exception('Empty cart');
        }

        $db->setQuery("Select * From #__breezingcommerce_plugins Where published = 1 And type = 'shipping' Order By `ordering`");
        $shipping_plugins = $db->loadAssocList();

        $data = CrBcCart::getData($order->id, $_cart_items, -1, -1);


        $_order_info = CrBcCart::getOrder(
            $order->id,
            $_cart,
            $_cart->getArray(),
            $_cart_items,
            $order->customer_id,
            $data,
            $shipping_plugins,
            array()
        );


        $reference = $_GET['txref'];
        $order_details = explode('_', $reference);

        $order_id = (int)$order_details[0];
        if ($order_id != $order->id) {
            throw new Exception('Transaction Reference not linked to order');
        }

        $result = $this->requery($rave, $reference);

        if ($result['result'] == 'success') {
            if ($_order_info->grand_total != $result['amount'] && $_order_info->history_currency_code != $result['currency']) {
                throw new Exception('Transaction Reference not linked to order');
            } else {
                return true;
            }
        } else {
            throw new Exception('Transaction not successful');

        }

        return false;
    }

    function getInitOutput()
    {

        $db = JFactory::getDBO();

        require_once(JPATH_COMPONENT_ADMINISTRATOR . DS . 'classes' . DS . 'CrBcCart.php');

        $_session_cart = JFactory::getSession()->get('crbc_cart', array());

        if (!isset($_session_cart['checkout']) || !isset($_session_cart['checkout']['payment_plugin_id'])) {
            throw new Exception('User checkout not performed yet');
        }

        $payment_plugin_id = intval($_session_cart['checkout']['payment_plugin_id']);

        $_cart = new CrBcCart($_session_cart);
        $_cart_items = $_cart->getItems(true);

        if (count($_cart_items) == 0) {

            throw new Exception('Trying to pay an empty cart');
        }

        $db->setQuery("Select * From #__breezingcommerce_plugins Where published = 1 And type = 'shipping' Order By `ordering`");
        $shipping_plugins = $db->loadAssocList();

        $data = CrBcCart::getData($_session_cart['order_id'], $_cart_items, -1, -1);

        $_order_info = CrBcCart::getOrder(
            $_session_cart['order_id'],
            $_cart,
            $_session_cart,
            $_cart_items,
            $_session_cart['customer_id'],
            $data,
            $shipping_plugins,
            array()
        );

        if ($_order_info->grand_total <= 0) {

            throw new Exception('Trying to use rave while the total is zero.');
        }

        $db->setQuery("Select * From " . $this->table . " Order By `" . $this->identity_column . "` Desc Limit 1");
        $rave = $db->loadObject();

        if (!($rave instanceof stdClass)) {
            throw new Exception('No rave payment setup found, please create one first in Admin => BreezingCommerce => Plugins => Rave');
        }



        $rave->url = '';
        $rave->business_name = CrBcHelpers::getBcConfig()->get('business_name', 'Default Shop Name');

        $rave->items = $_cart_items;

        $rave->tax = $_order_info->taxes;
        $rave->locale = JFactory::getApplication()->getLanguage()->getTag();
        $rave->locale = explode('-', $rave->locale);
        $rave->locale = $rave->locale[1];

        if (!empty($rave->force_locale)) {

            $rave->locale = $rave->force_locale;
        }

        $rave->currency = $_cart->currency_code;

        if (!empty($rave->force_currency)) {

            $rave->currency = $rave->force_currency;
        }

        $customer_id = $_session_cart['checkout']['userid'];
        $rave->email = $_order_info->customer->email;
        $rave->firstname = $_order_info->customer->firstname;
        $rave->lastname = $_order_info->customer->lastname;
        $rave->phone = $_order_info->customer->phone;
        $rave->amount = $_order_info->grand_total;

        $rave->no_shipping = $_cart->isVirtualOrder($_cart_items) ? 1 : 0;

        $rave->shipping = $_order_info->shipping_costs;

        $rave->order_id = $_session_cart['order_id'];

        $rave->payment_plugin_id = $payment_plugin_id;
        $rave->txref = $rave->order_id . '_' . time();


        $rave->redirect_url = JUri::getInstance()->toString() . '&order_id=' . $rave->order_id . '&verify_payment=1&payment_plugin_id=' . $rave->payment_plugin_id;

        ob_start();
        require_once(JPATH_SITE . '/media/breezingcommerce/plugins/payment/rave/site/tmpl/payment.php');
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Optional method to prevent this payment from being used if it's not suitable.
     * For example determing if user's location is actually suitable for the payment option.
     * If it returns false, the option won't be displayed upon checkout and also not being processed.
     * 
     * @return boolean
     */
    function isPaymentSuitable()
    {
        return true;
    }

    function getAfterPaymentInfo()
    {
        return JText::_('COM_BREEZINGCOMMERCE_RAVE_INFO_PAID');
    }

    public function getPluginDisplayName()
    {
        return JText::_('COM_BREEZINGCOMMERCE_RAVE');
    }

    public function getPluginIcon()
    {
        $img = JUri::root() . 'media/breezingcommerce/plugins/payment/rave/site/rave.png';
        return '<img src="' . $img . '" width="250px">';
    }

    public function getPluginDescription()
    {
        return JText::_('COM_BREEZINGCOMMERCE_RAVE_DESCRIPTION');
    }

    function getPaymentInfo()
    {
        $db = JFactory::getDBO();

        $db->setQuery("Select * From " . $this->table . " Order By `" . $this->identity_column . "` Desc Limit 1");
        $row = $db->loadObject();

        if (!($row instanceof stdClass)) {
            $row = new stdClass();
            $row->info = JText::_('No payment info available');
        }

        $id = $this->identity_column;

        $result = CrBcHelpers::loadTranslation($row->$id, 'plugin_payment_rave');

        if ($result) {

            $row->info = $result->body;
        }

        return "Rave";
    }
}