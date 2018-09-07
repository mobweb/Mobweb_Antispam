<?php
/**
 * @author    Louis Bataillard <info@mobweb.ch>
 * @package    Mobweb_Antispam
 * @copyright    Copyright (c) MobWeb GmbH (https://mobweb.ch)
 */
class Mobweb_Antispam_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_BANNED_TLDS = 'customer/antispam/banned_tlds';
    const XML_PATH_BANNED_STRINGS = 'customer/antispam/banned_strings';
    const XML_PATH_HIDDEN_FIELD_ACTIVE = 'customer/antispam/hidden_field_active';
    const HIDDEN_FIELD_NAME = 'hiddenfield';

    /**
     * Log helper
     *
     * @param String $message
     */
    public function log($message)
    {
        Mage::log($message, NULL, 'mobweb_antispam.log');
    }

    /**
     * To be used when spam is detected. Redirects the customer and immediately flushes the respone
     * to force redirection.
     */
    public function redirectAfterSpam()
    {
        $this->log('Spam detected:');
        
        // Get the request
        $request = Mage::app()->getRequest();

        // Log the spam submission
        $this->log(print_r($request->getPost(), true));

        // Get the referer URL (logic based on Mage_Core_Controller_Varien_Action::_getRefererUrl);
        $refererUrl = $request->getServer('HTTP_REFERER');
        if ($url = $request->getParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_REFERER_URL)) {
            $refererUrl = $url;
        }
        if ($url = $request->getParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_BASE64_URL)) {
            $refererUrl = Mage::helper('core')->urlDecodeAndEscape($url);
        }
        if ($url = $request->getParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_URL_ENCODED)) {
            $refererUrl = Mage::helper('core')->urlDecodeAndEscape($url);
        }

        if (!$refererUrl) {
            $refererUrl = Mage::app()->getStore()->getBaseUrl();
        }

        // Add a customer session exception
        Mage::getSingleton('customer/session')->addError($this->__('There has been a problem with your submission. Please try again.'));

        // Redirect to the previous page and send the response
        Mage::app()->getResponse()->setRedirect($_SERVER['HTTP_REFERER'])->sendResponse();

        // Exit to force instant redirection
        exit;
    }

    /**
     * Returns if the specified email address is considered spammy
     *
     * @param String $email
     * @return Boolean
     */
    public function isSpamEmail($email)
    {
        // Validate the email address
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

            // Get the TLD from the email address
            $tld = substr($email, strrpos($email, ".") + 1);
            if ($tld) {

                // Get the banned TLDs from the config
                $bannedTlds = explode(',', Mage::getStoreConfig(self::XML_PATH_BANNED_TLDS));
                if ($bannedTlds && count($bannedTlds)) {

                    // If the TLD is not banned, the email address is OK
                    if (!in_array($tld, $bannedTlds)) {
                        return false;
                    }
                }
            }
        }

        $this->log('Email address considered spam: ' . $email);

        return true;
    }
    
    /**
     * Returns if the specified field(s) data text is considered spammy
     *
     * @param Array|String $fields
     * @return Boolean
     */
    public function isSpamFields($fields)
    {
        $isSpam = false;

        // If only one field is passed, store it in an array so it can still be looped
        if (!is_array($fields)) {
            $fields = array($fields);
        }

        // Get the banned strings from the config
        $bannedStrings = explode(',', Mage::getStoreConfig(self::XML_PATH_BANNED_STRINGS));
        if ($bannedStrings && count($bannedStrings)) {

            // Loop through the fields
            foreach ($fields as $field) {

                // Regex match the field for the banned strings. If one is found, it's spam
                if (preg_match('(' . implode($bannedStrings, '|') . ')', $field) === 1) {
                    $this->log('Field value address considered spam: ' . $field);

                    return true;
                }
            }
        }

        return $isSpam;
    }

    /**
     * Returns the config value for the "hidden field" added to the contact form
     *
     * @return Boolean
     */
    public function hiddenFieldActivated()
    {
        return (boolean) Mage::getStoreConfig(self::XML_PATH_HIDDEN_FIELD_ACTIVE);
    }
}