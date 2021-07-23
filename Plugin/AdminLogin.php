<?php

namespace Vexpro\Autenticacao\Plugin;

use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Vexpro\Autenticacao\Helper\Curl as CurlHelper;

class AdminLogin
{
    protected $_userFactoryCollection;
    protected $_userFactory;
    protected $_curl;
    protected $_encryptor;
    protected $catalogSession;
    protected $scopeConfig;
    protected $resultRedirect;
    protected $curlHelper;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        UserCollectionFactory $userFactoryCollection,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\ResultFactory $result,
        CurlHelper $curlHelper
    ) {
        $this->curlHelper = $curlHelper;
        $this->logger = $logger;
        $this->_userFactoryCollection = $userFactoryCollection;
        $this->_curl = $curl;
        $this->_encryptor = $encryptor;
        $this->_userFactory = $userFactory;
        $this->catalogSession = $catalogSession;
        $this->scopeConfig = $scopeConfig;
        $this->resultRedirect = $result;
    }


    public function beforeLogin(\Magento\Backend\Model\Auth $authModel, $result, $username)
    {
        //Get Object Manager Instance
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $messageManager = $objectManager->get('Magento\Framework\Message\ManagerInterface');

        $cpf = preg_replace("/[^0-9]/", "", $result);

        $adminUsers = $this->_userFactoryCollection->create();
        $admin_exist = false;

        foreach ($adminUsers as $adminUser) {
            $username = $adminUser->getData('username');
            if ($username == $result) {
                $admin_exist = true;
            }
            if ($username == $cpf) {
                $admin_exist = true;
            }
        }

        if ($admin_exist) {
            $token = $this->curlHelper->getUserToken();

            // Salva o token em uma variável de sessão
            $this->catalogSession->setData('token', $token);

            $messageManager->addSuccess('Usuário e senha validados com sucesso');
        }
    }
}
