<?php

namespace Vexpro\Autenticacao\Block;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;


class Newemail extends \Magento\Framework\View\Element\Template
{

    protected $storeManager;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $customerAccountManagement
    ) {
        $this->storeManager = $storeManager;
        $this->customerAccountManagement = $customerAccountManagement;

        parent::__construct($context);
    }

    /**
     *
     * @return bool
     */
    public function emailExistOrNot($email): bool
    {
        $websiteId = (int)$this->storeManager->getWebsite()->getId();
        $isEmailNotExists = $this->customerAccountManagement->isEmailAvailable($email, $websiteId);
        return !$isEmailNotExists;
    }

    public function sayHello()
    {
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager  = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeID       = $storeManager->getStore()->getStoreId();
        $storeName     = $storeManager->getStore()->getName();

        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        if ($customerSession->isLoggedIn()) {
            $email = $customerSession->getCustomer()->getEmail(); // get Email
            $domain = explode("@", $email)[1];
            // if ($domain == "alterar"){

            // }
        }
        return __('Hello World');
    }
}
