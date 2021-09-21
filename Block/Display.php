<?php

namespace Vexpro\Autenticacao\Block;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Display extends \Magento\Framework\View\Element\Template
{
    protected $_curl;
    protected $scopeConfig;
    protected $_messageManager;

    protected $pontosCliente;
    protected $saldoCliente;

    /**
     * Undocumented function
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->_messageManager = $messageManager;
        $this->customerRepository = $customerRepository;
        setlocale(LC_MONETARY, "pt_BR");


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customer = $customerSession->getCustomer();
        $pontosCliente = $customer->getPontosCliente();
        $saldoCliente = $customer->getSaldoCliente();

        $pontos = 0;
        $saldo = 0;

        $fidelity = $customerSession->getFidelity();

        if ($fidelity > 0) {

            if (!$pontosCliente || !$saldoCliente) {
                $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
                $token = $customerSession->getCustomerToken();

                $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

                $url = $url_base . '/api/Consumer/GetCurrentConsumer';

                $this->_curl->addHeader("Accept", "text/plain");
                $this->_curl->addHeader("Authorization", 'bearer ' . $token);
                $this->_curl->get($url);
                $response = $this->_curl->getBody();
                $dados = json_decode($response);

                if ($dados != "") {
                    $pontos = $dados->points;
                    $saldo = $dados->digitalWalletBalance;
                    if ($pontos == "") {
                        $pontos = 0;
                    }
                    $customer->setCustomAttribute('pontos_cliente', $pontos);
                    $customer->setCustomAttribute('saldo_cliente', $saldo);
                }



                $pontosCliente = $pontos;
                $saldoCliente = $saldo;
            }
        }

        $this->pontosCliente = $pontosCliente;
        $this->saldoCliente = $saldoCliente;
    }

    /**
     * Instancia o cliente e carrega sua pontuação
     *
     * @return int
     */
    public function sayPoints()
    {
        return $this->pontosCliente;
    }

    public function sayWallet()
    {
        return $this->saldoCliente;
    }

    public function pointsConsumer()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');

        $programCurrencySymbol = $scopeConfig->getValue('acessos/general/programCurrencySymbol', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $programCurrencyName = $scopeConfig->getValue('acessos/general/programCurrencyName', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $pointsFormatted = number_format(floatval($this->sayPoints()), 0, ',', '.');

        return "{$programCurrencySymbol} {$pointsFormatted}";
    }

    public function walletConsumer()
    {
        $walletFormatted = number_format(floatval($this->sayWallet()), 2, ',', '.');

        return "R$ {$walletFormatted}";
    }
}
