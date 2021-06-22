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
     * @param \Magento\Framework\View\Element\Template\Context $context
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

    public function sayPoints()
	{
        // Instancia o cliente e carrega sua pontuação
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customer = $customerSession->getCustomer();
        $customerId = $customer->getId();
        $pontosCliente = $customer->getPontosCliente();

        $fidelity = $customerSession->getFidelity();
        $germiniToken = $customerSession->getCustomerToken();

        // $customer = $this->customerRepository->getById($customerId);
        // $cpfCliente = $customer->getCustomAttribute('cpf')->getValue();
        // $cpf_apenas_numeros = preg_replace("/[^0-9]/", "", $cpfCliente);


        // // Consulta a API do Germini para verificar se é fidelidade ou não
        // $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        // $url = $url_base . '/api/Consumer?cpf=' . $cpf_apenas_numeros ;

        // // get method
        // $this->_curl->get($url);

        // // output of curl request
        // $response = $this->_curl->getBody();

        // $dados = json_decode($response);
        // $fidelidade = $dados->fidelity->key;

        if ($fidelity == 1)
        {
            $this->_messageManager->getMessages(true);
            $this->_messageManager->addComplexNoticeMessage(
                'customerNeedValidateGermini',
                [
                    'url' => 'https://cvale-fidelidade-consumer.azurewebsites.net/auth/login',
                ]
            );
        }else if($fidelity == 0)
        {
            $this->_messageManager->getMessages(true);
            $this->_messageManager->addNotice('Vincule-se ao CVale Fidelidade');
        }

		return $pontosCliente;
	}
}
