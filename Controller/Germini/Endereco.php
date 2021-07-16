<?php
namespace Vexpro\Autenticacao\Controller\Germini;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Directory\Model\RegionFactory;

class Endereco extends Action
{
		protected $resultJsonFactory;
        protected $scopeConfig;
        protected $resultRedirectFactory;
        protected $messageManager;
        protected $regionFactory;

		/**
         * Endereco
         *
         * @param Context $context
         * @param JsonFactory $resultJsonFactory
         * @param ScopeConfigInterface $scopeConfig
         * @param RedirectFactory $redirectFactory
         * @param ManagerInterface $messageManager
         * @param RegionFactory $regionFactory
         */
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
            $post = $this->getRequest()->getPostValue();
            $postcode = $post['postcode'];

            $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $url = $url_base . '/api/Address/SearchZipCode/'. preg_replace("/[^0-9]/", "", $postcode);

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
            $resultado = json_decode($response);

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $region = $objectManager->create('Magento\Directory\Model\Region');

            $regionCode = $resultado->state;

            $regionId = $region->loadByCode($regionCode, "BR")->getId();

            $resultado->state = $regionId;

            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultJsonFactory->create();
            $resultJson->setData($resultado);

            return $resultJson;
		}
}
