<?php

/**
 *
 */
namespace Vexpro\Autenticacao\Plugin;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\UrlInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Quote\Model\QuoteRepository;
/**
 *
 */
class LoginPostPlugin
{
    protected $quoteRepository;
    protected $_encryptor;
    protected $_messageManager;
    protected $resultRedirect;
    protected $_curl;
    protected $storeManager;
    protected $customerFactory;
    protected $addressDataFactory;
    protected $_sessionFactory;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\SessionFactory $sessionFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        \Magento\Framework\HTTP\Client\Curl $curl,
        ResultFactory $result,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->_sessionFactory = $sessionFactory;
        $this->addressRepository = $addressRepository;
        $this->addressDataFactory = $addressDataFactory;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->_curl = $curl;
        $this->resultRedirect = $result;
        $this->_messageManager = $messageManager;
        $this->_encryptor = $encryptor;
    }

    // Autentica o usuário
    public function authenticate($customerId, $password)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerRegistry = $objectManager->get('Magento\Customer\Model\CustomerRegistry');
        $customerSecure = $customerRegistry->retrieveSecureData($customerId);
        $hash = $customerSecure->getPasswordHash();
        $teste = $this->_encryptor->validateHash($password, $hash);
        if (!$teste) {
            return false;
        }
        return true;
    }

    /**
     *
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param \Magento\Framework\Controller\Result\Redirect $result
     */
    public function afterExecute(
        \Magento\Customer\Controller\Account\LoginPost $subject,
        $result)
    {
        $errorMessage = "Cpf ou senha inválidos";
        $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();

        $cpf = $subject->getRequest()->getPost('login')['username'];
        $senha = $subject->getRequest()->getPost('login')['password'];

        //Get Object Manager Instance
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $messageManager = $objectManager->get('Magento\Framework\Message\ManagerInterface');
        $session = $objectManager->get('Magento\Customer\Model\Session');
        $responseHttp = $objectManager->get('Magento\Framework\App\Response\Http');

        // Tentar carregar o usuário usando o CPF informado
        $customerCollection = $objectManager->create('Magento\Customer\Model\ResourceModel\Customer\Collection');
        /** Applying Filters */
        $customerCollection
           ->addAttributeToSelect(array('email'))
           ->addAttributeToFilter('taxvat', array('eq' => $cpf));
        $customers = $customerCollection->load();

        $customer_id = 0;
        foreach ($customers as $customer) {
            $email = $customer->getEmail();
            $customer_id = $customer->getId();
        }
        
        // Se o usuário existe
        if ($customer_id > 0)
        {
            $customer = $objectManager->get('Magento\Customer\Model\Customer')->load($customer_id);
            // Realizo a autenticação desse usuário
            $res = $this->authenticate($customer_id, $senha);
            
            if($res == false){
                $this->_messageManager->addError($errorMessage);
                $result->setPath('/customer/account/login/');
                return $result;
            }

            // Crio a sessão desse usuário
            $customer->setWebsiteId($websiteId)->loadByEmail($customer->getEmail());
            $sessionManager = $this->_sessionFactory->create();
            $sessionManager->setCustomerAsLoggedIn($customer);

            $result->setPath('/customer/account/login/');
            return $result;
        } else {
            // Tenta realizar a autenticação com JWT
            $url = 'https://vxp-germini-identity-dev.azurewebsites.net/connect/token';
            $params = [
                "username" => $cpf,
                "password" => $senha,
                "client_id" => "ro.client.consumer",
                "client_secret" => "secret",
                "grant_type" => "password",
                "scope" => "germini-api openid profile"
            ];
            $this->_curl->post($url, $params);
            //response will contain the output in form of JSON string
            $response = $this->_curl->getBody();
            $resultado = json_decode($response);

            if ($response != "")
            {
                if (isset($resultado->error)){
                    $this->_messageManager->addError($errorMessage);
                    $result->setPath('/customer/account/login/');
                    return $result;
                }
                $token = json_decode($response)->access_token;

                // Com o token, cria o usuário com as informações do sistema germini
                $url = 'https://vxp-germini-kernel-dev.azurewebsites.net/api/Consumer/GetCurrentConsumer';
                
                $this->_curl->addHeader("Accept", "text/plain");
                $this->_curl->addHeader("Authorization", 'bearer '.$token);
                $this->_curl->get($url);
                $response = $this->_curl->getBody();
                $dados = json_decode($response);

                
                $new_customer = $objectManager->get('\Magento\Customer\Api\Data\CustomerInterfaceFactory')->create();
                $new_customer->setWebsiteId($websiteId);

                // Preparing data for new customer
                $new_customer->setEmail($dados->email);
                $name = $dados->name;
                $names = explode(" ", $name);
                $first_name = $names[0];
                $last_name = end($names);

                $new_customer->setFirstname($first_name);
                $new_customer->setLastname($last_name);
                $new_customer->setTaxVat($cpf);

                $pontos = $dados->points;
                $new_customer->setCustomAttribute('pontos_cliente', $pontos);

                $hashedPassword = $this->_encryptor->hash($senha);

                $objectManager->get('\Magento\Customer\Api\CustomerRepositoryInterface')->save($new_customer, $hashedPassword);

                $new_customer = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
                $new_customer->setWebsiteId($websiteId)->loadByEmail($dados->email);

                // Seta endereço do cliente

                // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                // $region = $objectManager->create('Magento\Directory\Model\Region')
                //                         ->loadByCode('CA', 'US');

                // TODO: regionId
                $addresss = $objectManager->get('\Magento\Customer\Model\AddressFactory');
                $address = $addresss->create();
                $address->setCustomerId($new_customer->getId())
                ->setFirstname($first_name)
                ->setLastname($last_name)
                ->setCountryId('BR')
                ->setPostcode($dados->address->zipcode)
                ->setCity($dados->address->city->name)
                ->setTelephone($dados->phoneNumber)
                ->setFax('')
                ->setCompany('')
                ->setStreet($dados->address->location)
                ->setIsDefaultBilling('1')
                ->setIsDefaultShipping('1')
                ->setSaveInAddressBook('1');
                $address->save();

                // Crio a sessão desse usuário
                // $customerSession = $objectManager->create('Magento\Customer\Model\Session');
                // $customerSession->setCustomerAsLoggedIn($new_customer);
                $sessionManager = $this->_sessionFactory->create();
                $sessionManager->setCustomerAsLoggedIn($new_customer);
    
            } else {
                $this->_messageManager->addError($errorMessage);
            }
        }
        $result->setPath('/customer/account/login/');
        return $result;
    }
}