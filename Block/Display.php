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
        $customerId = $customer->getId();
        $pontosCliente = $customer->getPontosCliente();

        $fidelity = $customerSession->getFidelity();
        $germiniToken = $customerSession->getCustomerToken();

		return $pontosCliente;
	}
}
