<?php

namespace Vexpro\Autenticacao\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Exception;

class Curl extends AbstractHelper
{
    const XML_PATH_IDENTITY = 'acessos/general/identity_url';
    const XML_PATH_KERNEL = 'acessos/general/kernel_url';
    const XML_PATH_ADMIN_LOGIN = 'acessos/general/identity_login';
    const XML_PATH_ADMIN_PASSWORD = 'acessos/general/identity_password';

    protected $token;
    protected $statusCode;
    protected $identityUrl;
    protected $kernelUrl;
    protected $adminLogin;
    protected $adminPassword;
    protected $_curl;


    /**
     * Config constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(Context $context, ScopeConfigInterface $scopeConfig, \Magento\Framework\HTTP\Client\Curl $curl)
    {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->_curl = $curl;
    }

    public function getIdentityUrl()
    {
        $this->identityUrl = $this->scopeConfig->getValue(self::XML_PATH_IDENTITY, ScopeInterface::SCOPE_STORE);
        if (empty($this->identityUrl)) {
            throw new Exception(__('No identity URL set in System > Configuration.'));
        }

        return $this->scopeConfig->getValue(self::XML_PATH_IDENTITY, ScopeInterface::SCOPE_STORE);
    }

    public function getKernelUrl()
    {
        $this->kernelUrl = $this->scopeConfig->getValue(self::XML_PATH_KERNEL, ScopeInterface::SCOPE_STORE);
        if (empty($this->kernelUrl)) {
            throw new Exception(__('No kernel URL set in System > Configuration.'));
        }

        return $this->scopeConfig->getValue(self::XML_PATH_KERNEL, ScopeInterface::SCOPE_STORE);
    }

    public function getAdminLogin()
    {
        $this->adminLogin = $this->scopeConfig->getValue(self::XML_PATH_ADMIN_LOGIN, ScopeInterface::SCOPE_STORE);
        if (empty($this->adminLogin)) {
            throw new Exception(__('No admin login set in System > Configuration.'));
        }

        return $this->scopeConfig->getValue(self::XML_PATH_ADMIN_LOGIN, ScopeInterface::SCOPE_STORE);
    }

    public function getAdminPassword()
    {
        $this->adminPassword = $this->scopeConfig->getValue(self::XML_PATH_ADMIN_PASSWORD, ScopeInterface::SCOPE_STORE);
        if (empty($this->adminPassword)) {
            throw new Exception(__('No admin password set in System > Configuration.'));
        }

        return $this->scopeConfig->getValue(self::XML_PATH_ADMIN_PASSWORD, ScopeInterface::SCOPE_STORE);
    }

    public function getUserToken()
    {
        $login = $this->getAdminLogin();
        $password = $this->getAdminPassword();
        $response = "";
        $url_base = $this->getIdentityUrl();
        $url = $url_base . '/connect/token';
        $params = [
            "username" => $login,
            "password" => $password,
            "client_id" => "ro.client.partner",
            "client_secret" => "secret",
            "grant_type" => "password",
            "scope" => "germini-api openid profile"
        ];
        $this->_curl->post($url, $params);
        $response = $this->_curl->getBody();
        $resultado = json_decode($response);

        if ($response == "" || isset($resultado->error)) {
            throw new Exception(__('Erro ao conectar com germini ou usuário e senha incorretos'));
        }

        $token = $resultado->access_token;

        return $token;
    }
}
