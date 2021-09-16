<?php

/**
 *
 */

namespace Vexpro\Autenticacao\Plugin;

use Magento\Customer\Model\Session as CustomerSession;
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
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;

/**
 *
 */
class UserPlugin
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
    protected $scopeConfig;
    protected $addressRepository;

    protected $cacheTypeList;
    protected $cacheFrontendPool;

    private function cleanCache()
    {
        $types = array('full_page');
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
    }


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
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool
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
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->cacheManager = $cacheManager;

        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
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
        $result
    ) {
        function mask($val, $mask)
        {
            $maskared = '';
            $k = 0;
            for ($i = 0; $i <= strlen($mask) - 1; ++$i) {
                if ($mask[$i] == '#') {
                    if (isset($val[$k])) {
                        $maskared .= $val[$k++];
                    }
                } else {
                    if (isset($mask[$i])) {
                        $maskared .= $mask[$i];
                    }
                }
            }

            return $maskared;
        }
        $errorMessage = "Cpf ou senha inválidos";
        // $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();
        $websiteId = 1;

        $username = $subject->getRequest()->getPost('login')['username'];
        // $cpf = preg_replace("/[^0-9]/", "", $username);
        $cpf = $username;
        $cpf_apenas_numeros = preg_replace("/[^0-9]/", "", $username);
        $cpf_mask = mask($cpf_apenas_numeros, '###.###.###-##');

        $senha = $subject->getRequest()->getPost('login')['password'];


        //     Tenta conectar com Germini usando JWT, caso consiga:
        //     Verifico se esse CPF já existe no magento, se existir então
        //     loga com esse usuário.
        //     Se o CPF náo exite no magento então cria esse usuário no magento.
        //     Se não conseguir conectar no germini tenta logar com os dados
        //     no magento.


        //Get Object Manager Instance
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $messageManager = $objectManager->get('Magento\Framework\Message\ManagerInterface');
        $session = $objectManager->get('Magento\Customer\Model\Session');
        $responseHttp = $objectManager->get('Magento\Framework\App\Response\Http');

        // Tentar carregar o usuário usando o CPF informado
        $customerCollection = $objectManager->create('Magento\Customer\Model\ResourceModel\Customer\Collection');
        /** Applying Filters */
        // $customerCollection
        //     //->addAttributeToSelect(array('email'))
        //     ->addAttributeToFilter('cpf', array('eq' => $cpf_apenas_numeros));
        // $customers = $customerCollection->load();

        // if ($customers->count() == 0) {
        //     $customerCollection
        //         //->addAttributeToSelect(array('email'))
        //         ->addAttributeToFilter('cpf', array('eq' => $cpf_mask));
        //     $customers = $customerCollection->load();
        // }

        $conta = 0;
        $customer_id = 0;


        $customerObj = $objectManager->create('Magento\Customer\Model\ResourceModel\Customer\Collection');
        $customer = $customerObj->addAttributeToSelect('*')
            ->addAttributeToFilter('cpf', $cpf_mask)
            ->load();

        if ($customer->count() > 0) {
            $customer_id = $customer->getData()[0]['entity_id'];
        }

        $customerObj = $objectManager->create('Magento\Customer\Model\ResourceModel\Customer\Collection');
        $customer = $customerObj->addAttributeToSelect('*')
            ->addAttributeToFilter('cpf', $cpf_apenas_numeros)
            ->load();

        if ($customer->count() > 0) {
            $customer_id = $customer->getData()[0]['entity_id'];
        }

        // VERIFICA SE TEM ALGUM CLIENTE COM O TAXVAT COM O CPF INFORMADO
        $customerObj = $objectManager->create('Magento\Customer\Model\ResourceModel\Customer\Collection');
        $customer = $customerObj->addAttributeToSelect('*')
            ->addAttributeToFilter('taxvat', $cpf_apenas_numeros)
            ->load();

        if ($customer->count() > 0) {
            $customer_id = $customer->getData()[0]['entity_id'];

            $taxvat = $customer->getData()[0]['taxvat'];
        }



        // foreach ($customers as $customer) {
        //     $conta++;
        //     $email = $customer->getEmail();
        //     $customer_id = $customer->getId();
        // }

        if ($customer_id == 0) {
            $customerCollection = $objectManager->create('Magento\Customer\Model\ResourceModel\Customer\Collection');
            $customerCollection
                ->addAttributeToFilter('cpf', array('eq' => $cpf));
            $customers = $customerCollection->load();

            $conta = 0;
            $customer_id = 0;
            foreach ($customers as $customer) {
                $conta++;
                $email = $customer->getEmail();
                $customer_id = $customer->getId();
            }
        }

        // Se o usuário existe
        if ($customer_id > 0) {



            // $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            // $url = $url_base . '/api/ConsumerWallet/GetConsumerPoints';

            // $url = $url . '?cpfCnpj=' . $cpf_apenas_numeros . '&password=' . $senha;

            // // get method
            // $this->_curl->get($url);

            // Tenta realizar a autenticação com JWT
            $url_base = $this->scopeConfig->getValue('acessos/general/identity_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            // $url_base = 'https://vxp-germini-identity-dev.azurewebsites.net/';
            $url = $url_base . '/connect/token';

            $params = [
                "username" => $cpf_apenas_numeros,
                "password" => $senha,
                "client_id" => "ro.client.consumer",
                "client_secret" => "secret",
                "grant_type" => "password",
                "scope" => "germini-api openid profile"
            ];
            $this->_curl->post($url, $params);

            // output of curl request
            $response = $this->_curl->getBody();

            $dados = json_decode($response);

            $customer = $this->customerRepository->getById($customer_id);
            if (!isset($dados->error) && $dados != '') {
                $this->customerRepository->save($customer, $this->_encryptor->getHash($senha, true));
            }



            // Realizo a autenticação desse usuário
            $res = $this->authenticate($customer_id, $senha);

            // Autentica no Magento
            if ($res == false) {
                $this->_messageManager->getMessages(true);
                $this->_messageManager->addError("CPF ou senha incorretos!");
                $result->setPath('customer/account/');
                return $result;
            }

            $customerSession = $objectManager->get('\Magento\Customer\Model\Session');

            $customerSession->setSapEdit(true);

            if (isset($dados->error)) {
                $fidelity = 2;
                if ($dados->error_description == "invalid_username") {
                    $fidelity = 0;
                }
            }

            if (isset($dados->access_token)) {
                $token = $dados->access_token;

                $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

                $url = $url_base . '/api/Consumer/GetCurrentConsumer';

                $this->_curl->addHeader("Accept", "text/plain");
                $this->_curl->addHeader("Authorization", 'bearer ' . $token);
                $this->_curl->get($url);
                $response = $this->_curl->getBody();
                $dados = json_decode($response);

                if ($dados->fidelity->key == 0) {
                    $fidelity = 2;
                } else {
                    $fidelity = $dados->status->key;
                }

                if ($dados == "") {
                    $pontos = 0;
                    $saldo = 0;
                } else {


                    $pontos = $dados->points;
                    $saldo = $dados->digitalWalletBalance;
                    if ($pontos == "") {
                        $pontos = 0;
                    }
                }
                $customerSession->setCustomerToken($token);
            } else {
                $pontos = 0;
                $saldo = 0;
                //     $this->_messageManager->getMessages(true);
                //     $this->_messageManager->addComplexNoticeMessage(
                //         'customerNeedValidateGermini',
                //         [
                //             'url' => 'https://cvale-fidelidade-consumer-hom.azurewebsites.net/auth/login',
                //         ]
                //     );

            }

            $customerSession->setFidelity($fidelity);
            $customer->setCustomAttribute('pontos_cliente', $pontos);
            $customer->setCustomAttribute('saldo_cliente', $saldo);



            $this->customerRepository->save($customer);

            if ($fidelity > 0) {
                if (isset($customer->getAddresses()[0])) {


                    if (isset($dados->email)) {
                        $customer->setEmail($dados->email);
                    }

                    if (isset($dados->dateOfBirth)) {
                        $dob = strtotime($dados->dateOfBirth);
                        $yearOfBirth = date("Y-m-d", $dob);
                        $customer->setDob($yearOfBirth);
                    }
                    // Atualizo o cpf com taxvat
                    // $customer->setCustomAttribute('cpf', $taxvat);
                    $this->customerRepository->save($customer, $this->_encryptor->getHash($senha, true));

                    $mainAddressId = $customer->getAddresses()[0]->getId();
                    $currAddress = $this->addressRepository->getById($mainAddressId);
                    $region = $objectManager->create('Magento\Directory\Model\Region');
                    $countryCode = 'BR';
                    // $regionId = $region->loadByCode($dados->address->state->abbreviation, $countryCode)->getId();

                    $address = $this->addressRepository->getById($mainAddressId);

                    $address->setCountryId($countryCode)
                        ->setCity($dados->address->city->name)
                        ->setStreet([$dados->address->location, $dados->address->number, $dados->address->district])
                        ->setPostCode($dados->address->zipCode)
                        ->setIsDefaultBilling(1)
                        ->setIsDefaultShipping(1);
                    // $address->setCountryId('BR'); // Update country id
                    // $address->setRegionId($regionId);
                    // $address->setCity('customCity'); // Update city

                    $this->addressRepository->save($address);
                    $customer->setDefaultBilling($address->getId());
                }
                $customer->setDefaultBilling($address->getId());
            }






            // } else {
            //     $this->_messageManager->getMessages(true);
            //     $this->_messageManager->addComplexNoticeMessage(
            //         'customerNeedValidateGermini',
            //         [
            //             'url' => 'https://cvale-fidelidade-consumer-hom.azurewebsites.net/auth/login',
            //         ]
            //     );
            //     return $result;

            // }
            $this->customerSession->setCustomerDataAsLoggedIn($customer);
            $result->setPath('/');
            $this->_messageManager->getMessages(true);
            $this->cleanCache();
            return $result;
        } else { // USUARIO NAO EXISTE AINDA NO MAGENTO
            // Tenta realizar a autenticação com JWT
            try {
                $url_base = $this->scopeConfig->getValue('acessos/general/identity_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                // $url_base = 'https://vxp-germini-identity-dev.azurewebsites.net/';
                $url = $url_base . '/connect/token';

                $params = [
                    "username" => $cpf_apenas_numeros,
                    "password" => $senha,
                    "client_id" => "ro.client.consumer",
                    "client_secret" => "secret",
                    "grant_type" => "password",
                    "scope" => "germini-api openid profile"
                ];
                $this->_curl->post($url, $params);
                //response will contain the output in form of JSON string
                $response = $this->_curl->getBody();
            } catch (\Exception $e) {


                // $this->_messageManager->addError('Não foi possível conectar com germini');
                $this->_messageManager->getMessages(true);

                $this->_messageManager->addComplexNoticeMessage(
                    'customerNeedValidateGermini',
                    [
                        'url' => 'https://cvale-fidelidade-consumer.azurewebsites.net/auth/login',
                    ]
                );

                $result->setPath('customer/account/');
                return $result;
            }

            $resultado = json_decode($response);

            if ($response != "" or !isset($resultado->error)) {
                if (isset($resultado->error)) {

                    $this->_messageManager->getMessages(true);
                    $this->_messageManager->addError("Usuário não encontrado no CVale Fidelidade");
                    // $this->_messageManager->addComplexNoticeMessage(
                    //     'customerNeedValidateGermini',
                    //     [
                    //         'url' => 'https://cvale-fidelidade-consumer.azurewebsites.net/auth/login',
                    //     ]
                    // );
                    // $this->_messageManager->addError("Não foi possível conectar com germini");
                    $result->setPath('customer/account/');
                    return $result;
                }
                $token = json_decode($response)->access_token;

                $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
                $customerSession->setCustomerToken($token);

                // Com o token, cria o usuário com as informações do sistema germini

                $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

                $url = $url_base . '/api/Consumer/GetCurrentConsumer';

                $this->_curl->addHeader("Accept", "text/plain");
                $this->_curl->addHeader("Authorization", 'bearer ' . $token);
                $this->_curl->get($url);
                $response = $this->_curl->getBody();
                $dados = json_decode($response);

                $fidelity = False;
                $fidelity = $dados->fidelity->key;

                $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
                $customerSession->setFidelity($fidelity);

                $new_customer = $objectManager->get('\Magento\Customer\Api\Data\CustomerInterfaceFactory')->create();
                $new_customer->setWebsiteId($websiteId);

                // Preparing data for new customer
                $new_customer->setEmail($dados->email);
                // $name = str_ireplace (' ', '', $dados->name);
                $names = explode(" ", ltrim($dados->name));
                $last_name = $names;
                array_shift($last_name);
                $last_name = join(" ", $last_name);
                $first_name = isset($names[0]) ? $names[0] : '';
                // $last_name = isset($names[1]) ? $names[1] : 'Cvale';
                if ($last_name == "")
                    $last_name = "Cvale";
                // if (sizeof($names) > 1) {
                //     $last_name = end($names);
                // } else {
                //     $last_name = $first_name;
                // }

                $new_customer->setFirstname($first_name);
                $new_customer->setLastname($last_name);
                $new_customer->setTaxVat($cpf);
                $new_customer->setCustomAttribute('cpf', $cpf);


                switch ($dados->gender) {
                    case 'M':
                        $magentoGender = 1;
                        break;
                    case 'F':
                        $magentoGender = 2;
                        break;
                    default:
                        $magentoGender = 3;
                        break;
                }
                // $magentoGender = $dados->gender == 'M' ? 1 : 2;
                $new_customer->setGender($magentoGender);

                $dateOfBirth = date_create($dados->dateOfBirth);
                $new_customer->setDob(date_format($dateOfBirth, 'Y-m-d'));

                $pontos = $dados->points;
                $saldo = $dados->digitalWalletBalance;
                if ($pontos == "") {
                    $pontos = 0;
                }
                $new_customer->setCustomAttribute('pontos_cliente', $pontos);
                $new_customer->setCustomAttribute('saldo_cliente', $saldo);



                $hashedPassword = $this->_encryptor->hash($senha);

                $objectManager->get('\Magento\Customer\Api\CustomerRepositoryInterface')->save($new_customer, $hashedPassword);

                $new_customer = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
                $new_customer->setWebsiteId($websiteId)->loadByEmail($dados->email);

                // Seta endereço do cliente
                try {
                    $regionCode = $dados->address->state->abbreviation;
                    $countryCode = 'BR';
                    $region = $objectManager->create('\Magento\Directory\Model\Region');
                    $regionId = $region->loadByCode($regionCode, $countryCode)->getId();

                    $telefone = $dados->phoneNumber;
                    if ($telefone == "") {
                        $telefone = $dados->phoneNumber2;
                    }

                    $addresss = $objectManager->get('\Magento\Customer\Model\AddressFactory');
                    $address = $addresss->create();
                    $address->setCustomerId($new_customer->getId())
                        ->setFirstname($first_name)
                        ->setLastname($last_name)
                        ->setCountryId($countryCode)
                        ->setRegionId($regionId)
                        ->setPostcode($dados->address->zipCode)
                        ->setCity($dados->address->city->name)
                        ->setTelephone($telefone)
                        ->setFax('')
                        ->setCompany('')
                        ->setStreet($dados->address->location)
                        ->setIsDefaultBilling('1')
                        ->setIsDefaultShipping('1')
                        ->setSaveInAddressBook('1');
                    $address->save();
                } catch (\Exception $e) {
                    $this->_messageManager->addError('Não foi possível carregar endereço');
                    // $result->setPath('customer/account/');
                    // return $result;
                }

                // Crio a sessão desse usuário
                $sessionManager = $this->_sessionFactory->create();
                $sessionManager->setCustomerAsLoggedIn($new_customer);
                $sessionManager->setConsumerPoints($pontos);
            } else {

                $this->_messageManager->getMessages(true);
                $this->_messageManager->addComplexNoticeMessage(
                    'customerNeedValidateGermini',
                    [
                        'url' => 'https://cvale-fidelidade-consumer.azurewebsites.net/auth/login',
                    ]
                );
                // $this->_messageManager->addError("Erro ao conectar com Germini");
            }
        }
        $this->cleanCache();
        $result->setPath('/');
        $this->_messageManager->getMessages(true);
        return $result;
    }
}
