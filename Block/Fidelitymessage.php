<?php
namespace Vexpro\Autenticacao\Block;

class Fidelitymessage extends \Magento\Framework\View\Element\Template
{
    protected $_messageManager;
		/**
		 * @param \Magento\Framework\View\Element\Template\Context $context
		 * @param array $data
		 */
		public function __construct(
				\Magento\Framework\View\Element\Template\Context $context,
                \Magento\Framework\Message\ManagerInterface $messageManager,
				array $data = []
		) {
				parent::__construct($context, $data);
                $this->_messageManager = $messageManager;
		}

        /**
         * Retorna fidelidade do usuário
         *
         * @return int
         */
        public function fidelity()
        {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerSession = $objectManager->create('Magento\Customer\Model\Session');
            $customer = $customerSession->getCustomer();
            $customerId = $customer->getId();
            $pontosCliente = $customer->getPontosCliente();

            $fidelity = $customerSession->getFidelity();

            return $fidelity;
        }

        public function baseUrl()
        {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $cvale_url = $objectManager
                ->get('Magento\Framework\App\Config\ScopeConfigInterface')
                ->getValue("acessos/general/cvale_url");

            return $cvale_url;
        }
}
