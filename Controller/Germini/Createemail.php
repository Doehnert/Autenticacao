<?php

namespace Vexpro\Autenticacao\Controller\Germini;

use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;



class Createemail extends Action
{
    protected $_pageFactory;
    protected $resultFactory;
    protected $storeManager;
    protected $_messageManager;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $customerAccountManagement,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_pageFactory = $pageFactory;
        $this->customerSession = $customerSession;
        $this->resultFactory = $resultFactory;
        $this->storeManager = $storeManager;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerRepository = $customerRepository;
        $this->_messageManager = $messageManager;


        return parent::__construct($context);
    }

    public function emailExistOrNot($email): bool
    {
        $websiteId = (int)$this->storeManager->getWebsite()->getId();
        $isEmailNotExists = $this->customerAccountManagement->isEmailAvailable($email, $websiteId);
        return !$isEmailNotExists;
    }

    public function execute()
    {
        $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $post = $this->getRequest()->getPostValue();
        $email = $post['email'];

        if ($this->emailExistOrNot($email)) {
            $this->_messageManager->getMessages(true);
            $this->_messageManager->addErrorMessage("O email já está em uso, escolha outro");

            $redirect->setUrl('/autentica/germini/newemail/');

            return $redirect;
        }

        $customer_id = $this->customerSession->getCustomer()->getId();
        $customer = $this->customerRepository->getById($customer_id);

        $customer->setEmail($email);

        $this->customerRepository->save($customer);


        $redirect->setUrl('/customer/account/');

        return $redirect;
    }
}
