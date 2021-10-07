<?php

namespace Vexpro\Autenticacao\Controller\Germini;

use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session as CustomerSession;



class Createemail extends Action
{
    protected $_pageFactory;
    protected $resultFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        CustomerSession $customerSession
    ) {
        $this->_pageFactory = $pageFactory;
        $this->customerSession = $customerSession;
        $this->resultFactory = $resultFactory;

        return parent::__construct($context);
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        $email = $post['email'];

        $customer_id = $this->customerSession->getCustomer()->getId();
        $customer = $this->customerRepository->getById($customer_id);

        $customer->setEmail($email);

        $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $redirect->setUrl('/customer/account/');

        return $redirect;
    }
}
