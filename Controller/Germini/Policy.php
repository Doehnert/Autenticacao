<?php

namespace Vexpro\Autenticacao\Controller\Germini;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Directory\Model\RegionFactory;

class Policy extends Action
{
    protected $resultJsonFactory;
    protected $scopeConfig;
    protected $resultRedirectFactory;
    protected $messageManager;
    protected $regionFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ScopeConfigInterface $scopeConfig,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        RegionFactory $regionFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->resultRedirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->regionFactory = $regionFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $url = $url_base . '/api/ProgramRules/Regulation';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: text/plain'
            ),
        ));

        // $response = curl_exec($curl);
        // $regras = json_decode($response);
        $regras = new \stdClass();
        $regras->policy = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($regras);

        return $resultJson;
    }
}
