<?php

namespace Vexpro\Autenticacao\Controller\Germini;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;

class Recebe extends Action
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
        // TODO: Retorna true se o cpf já existe no germini, false caso contrario
        $post = $this->getRequest()->getPostValue();
        $cpf = $post['cpf'];

        $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $url = $url_base . '/api/Consumer/VerifyDocument?cpfCnpj=' . preg_replace("/[^0-9]/", "", $cpf);

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

        // SE O USUÁRIO JÁ EXISTE NO GERMINI
        if ($resultado->success == true) {
            $resultJson = $this->resultJsonFactory->create();
            $resultJson->setData(True);
            return $resultJson;
        }
        // VERIFICA SE EXISTE ESSE CPF NO BP DO SAP
        $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $url = $url_base . "/api/Consumer/VerifyDocument?cpfCnpj={$cpf}&maskData=true";

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
        $cliente = json_decode($response);

        if ($cliente->success != true) {
            foreach ($cliente->errors as $error) {
                $this->messageManager->addErrorMessage(
                    $error->message
                );
            }
            $resultJson = $this->resultJsonFactory->create();
            $resultJson->setData(False);
            return $resultJson;
        }


        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($cliente);

        return $resultJson;

        echo false;
    }
}
