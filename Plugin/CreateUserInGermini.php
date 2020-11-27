<?php
namespace Vexpro\Autenticacao\Plugin;

use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\ResultFactory;

class CreateUserInGermini
{
    protected $timezone;
    protected $_curl;
    protected $scopeConfig;
    protected $resultRedirect;

    /** @var \Magento\Framework\UrlInterface */
    protected $urlModel;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * RestrictCustomerEmail constructor.
     * @param UrlFactory $urlFactory
     * @param RedirectFactory $redirectFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        UrlFactory $urlFactory,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ResultFactory $result,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    )
    {
        $this->_curl = $curl;
        $this->urlModel = $urlFactory->create();
        $this->resultRedirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        $this->resultRedirect = $result;
        $this->timezone = $timezone;
    }

    /**
     * @param \Magento\Customer\Controller\Account\CreatePost $subject
     * @param \Closure $proceed
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundExecute(
        \Magento\Customer\Controller\Account\CreatePost $subject,
        \Closure $proceed
    )
    {
        /** @var \Magento\Framework\App\RequestInterface $request */
        $email = $subject->getRequest()->getParam('email');
        $firstname = $subject->getRequest()->getParam('firstname');
        $lastname = $subject->getRequest()->getParam('lastname');
        $password = $subject->getRequest()->getParam('password');
        $password_confirmation = $subject->getRequest()->getParam('password_confirmation');
        $cpf = $subject->getRequest()->getParam('cpf');
        $dob = $subject->getRequest()->getParam('dob');
        $gender = $subject->getRequest()->getParam('gender');

        $gender = $gender === "2" ? 'f' : 'm';
        $dob = date("Y-m-d H:i:s", strtotime($dob));
        // $dob = $this->timezone->date(new \DateTime($dob))->format('Y-m-d H:i:s');

        // Cria usuário no Germini
        $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $response = "";
        $url = $url_base . '/api/Consumer/Register';
        $params = [
            "name" => $firstname,
            "cpf" => $cpf,
            "email" => $email,
            "password" => $password,
            "confirmPassword" => $password_confirmation,
            "gender" => $gender,
            "dateOfBirth" => $dob
        ];

        $data_json = json_encode($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: text/plain'));

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response  = curl_exec($ch);

        curl_close($ch);
        $resultado = json_decode($response);

        if ($resultado->errors == ''){
            return $proceed();
        } else {
            foreach ($resultado->errors as $error){
                $this->messageManager->addErrorMessage(
                    $error->message
                );
            }
            return $this->resultRedirectFactory->create()
            ->setPath(
                'customer/account/create'
            );
        }



        // list($nick, $domain) = explode('@', $email, 2);
        // if (in_array($domain, ['163.com', 'mail.ru'], true)) {

        //     $this->messageManager->addErrorMessage(
        //         'Registration is disabled for you domain'
        //     );
        //     $defaultUrl = $this->urlModel->getUrl('*/*/create', ['_secure' => true]);
        //     /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        //     $resultRedirect = $this->resultRedirectFactory->create();

        //     return $resultRedirect->setUrl($defaultUrl);

        // }

    }
}
