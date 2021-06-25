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

        public function fidelity()
        {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerSession = $objectManager->create('Magento\Customer\Model\Session');
            $customer = $customerSession->getCustomer();
            $customerId = $customer->getId();
            $pontosCliente = $customer->getPontosCliente();

            $fidelity = $customerSession->getFidelity();

            return $fidelity;

            // if (1 == 1)
            // {
            //     $this->_messageManager->getMessages(true);
            //     $this->_messageManager->addComplexNoticeMessage(
            //         'customerNeedValidateGermini',
            //         [
            //             'url' => 'https://cvale-fidelidade-consumer.azurewebsites.net/auth/login',
            //         ]
            //     );
            // }
            // else if($fidelity == 0)
            // {
            //     $this->_messageManager->getMessages(true);
            //     $this->_messageManager->addNotice('Vincule-se ao CVale Fidelidade');
            // }
        }
}
