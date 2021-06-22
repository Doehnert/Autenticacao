<?php
namespace Vexpro\Autenticacao\Controller\Germini;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Directory\Model\RegionFactory;
use \Magento\Directory\Model\CountryFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Registrargermini extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $scopeConfig;
    protected $resultRedirectFactory;
    protected $messageManager;
    protected $regionFactory;
    protected $countryFactory;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
         JsonFactory $resultJsonFactory,
         ScopeConfigInterface $scopeConfig,
         RedirectFactory $redirectFactory,
         ManagerInterface $messageManager,
         RegionFactory $regionFactory,
         CountryFactory $countryFactory,
         Context $context
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->resultRedirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->regionFactory = $regionFactory;
        $this->countryFactory = $countryFactory;
        $this->customerRepository = $customerRepository;
        return parent::__construct($context);
    }

    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customer = $customerSession->getCustomer();
        $customerId = $customer->getId();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logger = $objectManager->create('\Psr\Log\LoggerInterface');

        $firstname = $customer->getFirstname();
        $lastname = $customer->getLastname();

        $name = "{$firstname} {$lastname}";
        $nickname = $firstname;

        $customerId = $customer->getId();
        $customer = $this->customerRepository->getById($customerId);
        $cpf = preg_replace("/[^0-9]/", "", $customer->getCustomAttribute('cpf')->getValue());

        $gender = $customer->getGender();
        $dateOfBirth = $customer->getDob();
        $email = $customer->getEmail();

        $address = $customer->getAddresses()[0];

        $phoneNumber = preg_replace("/[^0-9]/", "",$address->getTelephone());
        $phoneNumber2 = preg_replace("/[^0-9]/", "",$address->getTelephone());
        $associated = true;
        $zipCode = preg_replace("/[^0-9]/", "", $address->getPostcode());
        $location = $address->getStreet()[0];
        $district = $address->getStreet()[2];
        $number = $address->getStreet()[1];
        $aditionalInfo = '';
        $addressType = 1;

        $state = $address->getRegion()->getRegionCode();
        $city = $address->getCity();

        $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $germiniGenero = $gender == "1" ? 'm' : 'f';

        // Cria usuário no Germini
        $response = "";
        $url = $url_base . '/api/Consumer/CreateUpdateConsumer';
        $params = array(
            "name" => $name,
            "nickname" => $nickname,
            "cpf" => $cpf,
            "gender" => $germiniGenero,
            "email" => $email,
            "phoneNumber" => $phoneNumber,
            "phoneNumber2" => $phoneNumber2,
            "associated" => true,
            "address" => array( 0 => [
                "zipCode" => $zipCode,
                "location" => $location,
                "district" => $district,
                "number" => $number,
                "aditionalInfo" => $aditionalInfo,
                "addressType" => 1,
                "state" => $state,
                "city" => $city,
            ])
        );

        $data_json = json_encode($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: text/plain'));

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response  = curl_exec($ch);

        curl_close($ch);
        $resultado = json_decode($response);


        if (!isset($resultado->errors)){
            $this->messageManager->addSuccessMessage(
                "Usuário vinculado ao germini com sucesso"
            );
            return $this->resultRedirectFactory->create()
            ->setPath(
                'customer/account/create'
            );
        }
    }
}
