<?php
/**
 * @author    Louis Bataillard <info@mobweb.ch>
 * @package    Mobweb_Antispam
 * @copyright    Copyright (c) MobWeb GmbH (https://mobweb.ch)
 */
class Mobweb_Antispam_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Observes the newsletter subscription controller and checks the posted data for spam
     *
     * @param Varien_Event_Observer $observer
     */
    public function controllerActionPredispatchNewsletterSubscriberNew($observer)
    {
        $helper = Mage::helper('mobweb_antispam');
        $isSpam = false;

        $helper->log('Checking newsletter subscription submission for spam');

        // Get the data from the request
        $request = Mage::app()->getRequest();
        if ($request && $request->isPost() && $request->getPost('email')) {
            $email = (string) $request->getPost('email');

            // Validate the email address
            if ($helper->isSpamEmail($email)) {
                $isSpam = true;
            }
        }

        // Check if the request is considered spam
        if ($isSpam) {

            // Redirect the user to the previous page so they can fix their mistake
            $helper->redirectAfterSpam();
        }

        // No spam detected, continue
        $helper->log('No spam detected!');
        return $this;
    }

    /**
     * Observes the customer registration controller and checks the posted data for spam
     *
     * @param Varien_Event_Observer $observer
     */
    public function controllerActionPredispatchCustomerAccountCreatePost($observer)
    {
        $helper = Mage::helper('mobweb_antispam');
        $isSpam = false;

        $helper->log('Checking customer account registration for spam');

        // Get the data from the request
        $request = Mage::app()->getRequest();
        if ($request && $request->isPost() && $request->getPost('email')) {
            $email = (string) $request->getPost('email');

            // Validate the email address
            if ($helper->isSpamEmail($email)) {
                $isSpam = true;
            }

            // Validate the other free text fields: First- and last name
            $firstname = $request->getPost('firstname');
            $lastname = $request->getPost('lastname');
            if ($helper->isSpamFields(array($firstname, $lastname))) {
                $isSpam = true;
            }
        }

        // Check if the request is considered spam
        if ($isSpam) {

            // Redirect the user to the previous page so they can fix their mistake
            $helper->redirectAfterSpam();
        }

        // No spam detected, continue
        $helper->log('No spam detected!');
        return $this;
    }
}