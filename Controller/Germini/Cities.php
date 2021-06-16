<?php
namespace Vexpro\Autenticacao\Controller\Germini;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Directory\Model\RegionFactory;
use \Magento\Directory\Model\CountryFactory;

class Cities extends Action
{
		protected $resultJsonFactory;
        protected $scopeConfig;
        protected $resultRedirectFactory;
        protected $messageManager;
        protected $regionFactory;
        protected $countryFactory;

		public function __construct(
			 Context $context,
			 JsonFactory $resultJsonFactory,
             ScopeConfigInterface $scopeConfig,
             RedirectFactory $redirectFactory,
             ManagerInterface $messageManager,
             RegionFactory $regionFactory,
             CountryFactory $countryFactory
		)
		{
			$this->resultJsonFactory = $resultJsonFactory;
            $this->scopeConfig = $scopeConfig;
            $this->resultRedirectFactory = $redirectFactory;
            $this->messageManager = $messageManager;
            $this->regionFactory = $regionFactory;
            $this->countryFactory = $countryFactory;
			return parent::__construct($context);
		}

		public function execute()
		{
            $post = $this->getRequest()->getPostValue();
            $region_id = $post['region_id'];
            $country_code = $post['country'];
            $region = $this->regionFactory->create()->load($region_id);
            $region_code = $region->getCode();


            // Obtem o id do germini referente ao country_code

            $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $url = $url_base . '/api/Country';

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
            $paises = json_decode($response);

            $meu_pais = '';
            foreach($paises as $pais){
                if ($pais->code == $country_code){
                    $meu_pais = $pais;
                    break;
                }
            }

            $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            // $url = $url_base . '/api/State/?countryId=' . $meu_pais->id;
            $url = $url_base . '/api/State/?countryId=20b32dbd-8bda-4563-bcd5-0a7e827fc5e4';

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
            $estados = json_decode($response);

            $meu_estado = '';
            foreach ($estados as $estado){

                if ($estado->abbreviation == $region_code){
                    $meu_estado = $estado;
                    break;
                }
            }

            $germini_region_id = $meu_estado->id;

            $url = $url_base . '/api/City/?stateId=' . $germini_region_id;

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
            $cidades = json_decode($response);

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultJsonFactory->create();
            $resultJson->setData($cidades);

            return $resultJson;
		}
}
