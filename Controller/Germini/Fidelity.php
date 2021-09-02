<?php

namespace Vexpro\Autenticacao\Controller\Germini;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;

class Fidelity extends Action
{
    protected $resultJsonFactory;
    protected $scopeConfig;
    protected $resultRedirectFactory;
    protected $messageManager;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ScopeConfigInterface $scopeConfig,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->resultRedirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        return parent::__construct($context);
    }

    public function execute()
    {
        // TODO: Verifica se o email informado é de fidelidade ou não

        $post = $this->getRequest()->getPostValue();
        $email_address = $post['email_address'];

        // Encontra o CPF correspondente com o email

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerObj = $objectManager->create('Magento\Customer\Model\ResourceModel\Customer\Collection');
        $customer = $customerObj->addAttributeToSelect('*')
            ->addAttributeToFilter('email', $email_address)
            ->load();

        if ($customer->count() == 0) {
            return false;
        }


        $cpf = $customer->getData()[0]['taxvat'];
        // $cpf = $customer->getTaxvat();


        $url_base = $this->scopeConfig->getValue('acessos/general/identity_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        // $url = "{$url_base}/api/Consumer/ValidateConsumerByEmail?email={$email_address}";

        $url = $url_base . '/api/Account/ListUsersByLogin/' . preg_replace("/[^0-9]/", "", $cpf);

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

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($resultado);

        return $resultJson;
    }
}
