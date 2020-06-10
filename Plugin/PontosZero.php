<?php
namespace Vexpro\Autenticacao\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AccountManagement;

class PontosZero
{
    public function __construct()
    {
        
    }


    public function beforeCreateAccountWithPasswordHash(AccountManagement $subject,CustomerInterface $customer, $hash, $redirectUrl)
    {
        $customer->setCustomAttribute('pontos_cliente', 0);
        return [$customer, $hash, $redirectUrl];
    }


}
