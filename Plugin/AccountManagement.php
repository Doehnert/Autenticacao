<?php

namespace Vexpro\Autenticacao\Plugin;

use Magento\Framework\Exception\LocalizedException;

class AccountManagement
{
    const BLACK_LIST_CUSTOMER_GROUP = 4;

    /**
     * Authenticate a customer
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param \Magento\Customer\Api\Data\CustomerInterface $result
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterAuthenticate(
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        $result
    )
    {
        if($result->getGroupId() == self::BLACK_LIST_CUSTOMER_GROUP) {
            throw new LocalizedException(__('The customer group does not allow.'));
        }
        $result->setEmail('iisabellyfatimadias@fortlar.com.br');
        return $result;
    }
}