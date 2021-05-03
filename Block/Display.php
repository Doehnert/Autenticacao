<?php
namespace Vexpro\Autenticacao\Block;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

class Display extends \Magento\Framework\View\Element\Template
{
    protected $_curl;
    protected $scopeConfig;
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
    }

    public function sayPoints()
	{
        // Instancia o cliente e carrega sua pontuação
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customer = $customerSession->getCustomer();
        $customerId = $customer->getId();
        $pontosCliente = $customer->getPontosCliente();

        // Consulta a API do Germini para pegar a pontuação
        // $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        // $url = $url_base . '/api/ConsumerWallet/GetConsumerPoints';
        // $url = $url . '?cpfCnpj=' . $cpf_apenas_numeros . '&password=' . $senha;

        // // get method
        // $this->_curl->get($url);

        // // output of curl request
        // $response = $this->_curl->getBody();

        // $dados = json_decode($response);

        // if ($dados == "") {
        //     $pontos = 0;
        // } else {
        //     $pontos = $dados->data;
        //     if ($pontos == "") {
        //         $pontos = 0;
        //     }
        // }

        // $customer->setCustomAttribute('pontos_cliente', $pontos);

		return $pontosCliente;
	}
}
