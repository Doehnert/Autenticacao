<?php

namespace Vexpro\Autenticacao\Block;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Display extends \Magento\Framework\View\Element\Template
{
    protected $_curl;
    protected $scopeConfig;
    protected $_messageManager;

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
    }

    /**
     * Instancia o cliente e carrega sua pontuação
     *
     * @return int
     */
    public function sayPoints()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customer = $customerSession->getCustomer();
        $pontosCliente = $customer->getPontosCliente();

        return $pontosCliente;
    }

    public function sayWallet()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customer = $customerSession->getCustomer();
        $saldoCliente = $customer->getSaldoCliente();

        return $saldoCliente;
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
