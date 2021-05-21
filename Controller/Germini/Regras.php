<?php
namespace Vexpro\Autenticacao\Controller\Germini;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Directory\Model\RegionFactory;

class Regras extends Action
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
		)
		{
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
            $url = $url_base . '/api/PlatformRules';

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

            $response = curl_exec($curl);
            $regras = json_decode($response);

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultJsonFactory->create();
            $resultJson->setData($regras);

            return $resultJson;
		}
}
