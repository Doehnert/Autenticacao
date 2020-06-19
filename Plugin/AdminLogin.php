<?php
namespace Vexpro\Autenticacao\Plugin;

use  Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;


class AdminLogin
{
    private $logger;
    protected $_userFactoryCollection;
    protected $_userFactory;
    protected $_curl;
    protected $_encryptor;
    protected $catalogSession;
    protected $scopeConfig;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        UserCollectionFactory $userFactoryCollection,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->logger = $logger;
        $this->_userFactoryCollection = $userFactoryCollection;
        $this->_curl = $curl;
        $this->_encryptor = $encryptor;
        $this->_userFactory = $userFactory;
        $this->catalogSession = $catalogSession;
        $this->scopeConfig = $scopeConfig;
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

    public function beforeLogin(\Magento\Backend\Model\Auth $authModel, $result, $username)
    {
        $this->logger->debug('User ' . $result . ' signed in.');

        $cpf = preg_replace("/[^0-9]/", "", $result);
        $senha = $username;

        $adminUsers = $this->_userFactoryCollection->create();

        $admin_exist = false;

        foreach($adminUsers as $adminUser) {
            $username = $adminUser->getData('username');
            if ($username == $result){
                $admin_exist = true;
                return;
            }
            if ($username == $cpf){
                $admin_exist = true;
            }
        }

        // DEBUG
        // $admin_exist = false;

        //Get Object Manager Instance
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $messageManager = $objectManager->get('Magento\Framework\Message\ManagerInterface');
        $session = $objectManager->get('Magento\Customer\Model\Session');
        $responseHttp = $objectManager->get('Magento\Framework\App\Response\Http');

        // $url_base = 'https://cvale-fidelidade-identity-dev.azurewebsites.net';
        $url_base = $this->scopeConfig->getValue('acessos/general/identity_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($admin_exist)
        {
            // Se usuario nao existe no bd entao verifica se existe no germini

            // Tenta realizar a autenticação com JWT
            $response = "";
            $url = $url_base . '/connect/token';
            // $url = 'https://vxp-germini-identity-dev.azurewebsites.net/connect/token';
                $params = [
                    "username" => $cpf,
                    "password" => $senha,
                    "client_id" => "ro.client.partner",
                    "client_secret" => "secret",
                    "grant_type" => "password",
                    "scope" => "germini-api openid profile"
                ];
                $this->_curl->post($url, $params);
                //response will contain the output in form of JSON string
                $response = $this->_curl->getBody();


            $resultado = json_decode($response);

            // print_r($resultado);

            // Se o usuário nao existe no germini
            if ($response == "")
            {
                $messageManager->addError('Usuário não existe no Germini');
                exit;
            } else {
                if (isset($resultado->error))
                {
                    $messageManager->addError('Erro ao conectar com germini');
                    exit;
                } else {
                    $token = json_decode($response)->access_token;
                }
            }
        }

        // Se o usuário ainda náo existe no banco de dados do magento
        else
        {
            // Com o token, cria o usuário com as informações do sistema germini
            // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            // $url_base = 'https://cvale-fidelidade-kernel-dev.azurewebsites.net';
            // $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            
            // $url = $url_base . '/api/Partner/GetCurrentPartner';


            // $this->_curl->addHeader("Accept", "text/plain");
            // $this->_curl->addHeader("Authorization", 'bearer '.$token);
            // $this->_curl->get($url);
            // $response = $this->_curl->getBody();
            // $dados = json_decode($response);

            $response = "";
            $url = $url_base . '/connect/token';
                $params = [
                    "username" => $cpf,
                    "password" => $senha,
                    "client_id" => "ro.client.partner",
                    "client_secret" => "secret",
                    "grant_type" => "password",
                    "scope" => "germini-api openid profile"
                ];
                $this->_curl->post($url, $params);
                //response will contain the output in form of JSON string
                $response = $this->_curl->getBody();


            $dados = json_decode($response);
            $token = $dados->access_token;

            
            $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $response = "";
            $url = $url_base . '/api/Partner/GetCurrentPartner';
            
            $this->_curl->addHeader("Accept", "text/plain");
            $this->_curl->addHeader("Authorization", 'bearer '.$token);
            $this->_curl->get($url);
            $response = $this->_curl->getBody();
            $dados = json_decode($response);

            $name = $dados->name;
            $names = explode(" ", $name);
            $first_name = $names[0];
            $last_name = end($names);

            $hashedPassword = $this->_encryptor->hash($senha);
            
            // Cria esse usuário no admin do magento
            $adminInfo = [
                'username'  => $cpf,
                'firstname' => $first_name,
                'lastname'    => $last_name,
                'email'     => $dados->email,
                'password'  => $senha,
                'interface_locale' => 'pt_BR',
                'is_active' => 1
            ];
                        
            $userModel = $this->_userFactory->create();
            $userModel->setData($adminInfo);
            $userModel->setRoleId(1);
            try{
            $userModel->save(); 
            } catch (\Exception $ex) {
                
                $messageManager->addError($ex->getMessage());
                exit;
            }

            // Após criar a conta, faz login com ela
            $messageManager->addSuccess(__('Conta associada com sucesso, entre novamente'));

        }
        // Salva o token em uma variável de sessão
        $this->catalogSession->setData('token', $token);
    }
}
