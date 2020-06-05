<?php
namespace Vexpro\Autenticacao\Block;

class Display extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function sayPoints()
	{
        // Instancia o cliente e carrega sua pontuação
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customer = $customerSession->getCustomer();
        $customerId = $customer->getId();
        $pontosCliente = $customer->getPontosCliente();
		return $pontosCliente;
	}
}
