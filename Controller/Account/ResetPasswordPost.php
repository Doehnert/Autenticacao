<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vexpro\Autenticacao\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\InputException;
use Magento\Customer\Model\Customer\CredentialsValidator;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\ForgotPasswordToken\GetCustomerByToken;
use Magento\Framework\App\ObjectManager;



/**
 * Customer reset password controller
 */
class ResetPasswordPost extends \Magento\Customer\Controller\AbstractAccount implements HttpPostActionInterface
{
    protected $scopeConfig;
    protected $_encryptor;
    protected $_curl;
    /**
     * @var GetCustomerByToken
     */
    private $getByToken;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $accountManagement;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $accountManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param CredentialsValidator|null $credentialsValidator
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        Context $context,
        Session $customerSession,
        AccountManagementInterface $accountManagement,
        CustomerRepositoryInterface $customerRepository,
        CredentialsValidator $credentialsValidator = null,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        GetCustomerByToken $getByToken
    ) {
        $objectManager = ObjectManager::getInstance();
        $this->_curl = $curl;
        $this->_encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
        $this->session = $customerSession;
        $this->accountManagement = $accountManagement;
        $this->customerRepository = $customerRepository;

        $this->getByToken = $getByToken
            ?: $objectManager->get(GetCustomerByToken::class);

        parent::__construct($context);
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
     * Reset forgotten password
     *
     * Used to handle data received from reset forgotten password form
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resetPasswordToken = (string)$this->getRequest()->getQuery('token');
        $password = (string)$this->getRequest()->getPost('password');
        $passwordConfirmation = (string)$this->getRequest()->getPost('password_confirmation');

        $oldPassword = (string)$this->getRequest()->getPost('oldPassword');

        if ($password !== $passwordConfirmation) {
            $this->messageManager->addErrorMessage(__("New Password and Confirm New Password values didn't match."));
            $resultRedirect->setPath('*/*/createPassword', ['token' => $resetPasswordToken]);

            return $resultRedirect;
        }
        if (iconv_strlen($password) <= 0) {
            $this->messageManager->addErrorMessage(__('Please enter a new password.'));
            $resultRedirect->setPath('*/*/createPassword', ['token' => $resetPasswordToken]);

            return $resultRedirect;
        }

        $customer = $this->getByToken->execute($resetPasswordToken);
        $user_id = $customer->getId();

        $cpf = $customer->getCustomAttribute('cpf')->getValue();
        $email = $customer->getEmail();
        $cpf_apenas_numeros = preg_replace("/[^0-9]/", "", $cpf);

        try {

            // CHANGE PASSWORD IN GERMINI
            $url_base = $this->scopeConfig->getValue('acessos/general/identity_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            // // 1) Get User id
            $url = "{$url_base}/api/Account/GetUserByLogin/{$cpf_apenas_numeros}";

            $this->_curl->get($url);

            // output of curl request
            $response = $this->_curl->getBody();
            $user_data = json_decode($response);

            $user_germini_id = 0;

            if ($user_data) {
                $user_germini_id = $user_data->id;
            }

            if ($user_germini_id !== 0) { // é do fidelidade

                $url = $url_base . '/api/Account/RequestResetToken/' . $cpf_apenas_numeros;

                $this->_curl->get($url);

                // output of curl request
                $response = $this->_curl->getBody();
                $rest = json_decode($response);

                $token = 0;
                $token = $rest->token;

                if ($token === 0) {
                    throw new \Exception("Erro ao buscar Token de reset do Germini");
                }

                if ($token !== 0) {
                    $url = $url_base . '/api/Account/ResetPassword';

                    $params = [
                        "id" => $user_germini_id,
                        "token" => $token,
                        "email" => $email,
                        "newPassword" => $password
                    ];
                    $data_json = json_encode($params);

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "PUT",
                        CURLOPT_POSTFIELDS => $data_json,
                        CURLOPT_HTTPHEADER => array(
                            "cache-control: no-cache",
                            "content-type: application/json",
                        ),
                    ));

                    $response = curl_exec($curl);
                    $rest = json_decode($response);
                    $err = curl_error($curl);

                    curl_close($curl);

                    if ($response == "") {
                        throw new \Exception("Erro ao atualizar a senha no Germini");
                    }
                }
            }


            $this->accountManagement->resetPassword(
                null,
                $resetPasswordToken,
                $password
            );
            // logout from current session if password changed.
            if ($this->session->isLoggedIn()) {
                $this->session->logout();
                $this->session->start();
            }
            $this->session->unsRpToken();
            $this->messageManager->addSuccessMessage(__('You updated your password.'));
            $resultRedirect->setPath('*/*/login');

            return $resultRedirect;
        } catch (InputException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            foreach ($e->getErrors() as $error) {
                $this->messageManager->addErrorMessage($error->getMessage());
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Something went wrong while saving the new password.'));
        }
        $resultRedirect->setPath('*/*/createPassword', ['token' => $resetPasswordToken]);

        return $resultRedirect;
    }
}
