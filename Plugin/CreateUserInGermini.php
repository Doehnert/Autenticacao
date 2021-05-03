<?php
namespace Vexpro\Autenticacao\Plugin;

use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Directory\Model\RegionFactory;

class CreateUserInGermini
{
    protected $regionFactory;
    protected $timezone;
    protected $_curl;
    protected $scopeConfig;
    protected $resultRedirect;
    protected $_countryFactory;

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
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory
    )
    {
        $this->_countryFactory = $countryFactory;
        $this->_curl = $curl;
        $this->urlModel = $urlFactory->create();
        $this->resultRedirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        $this->resultRedirect = $result;
        $this->timezone = $timezone;
        $this->regionFactory = $regionFactory;
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

        $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);


        $email = $subject->getRequest()->getParam('email');
        $firstname = $subject->getRequest()->getParam('firstname');
        $lastname = $subject->getRequest()->getParam('lastname');
        $password = $subject->getRequest()->getParam('password');
        $password_confirmation = $subject->getRequest()->getParam('password_confirmation');
        $cpf = $subject->getRequest()->getParam('cpf');
        $dob = $subject->getRequest()->getParam('dob');
        $gender = $subject->getRequest()->getParam('gender');

        $location = $subject->getRequest()->getParam('street')[0];
        $number = $subject->getRequest()->getParam('street')[1];
        $district = $subject->getRequest()->getParam('street')[2];
        $zipCode = $subject->getRequest()->getParam('postcode');

        $regionId = $subject->getRequest()->getParam('region_id'); //499
        $region = $this->regionFactory->create()->load($regionId);
        $stateId = $region->getCode();
        $countryId = $region->getCountryId();
        $country = $this->_countryFactory->create()->loadByCode($countryId);
        $countryName = $country->getName();
        $cityId = $subject->getRequest()->getParam('city');
        $phone2 = $subject->getRequest()->getParam('telephone');
        $phone2 = preg_replace("/[^0-9]/", "",$phone2);

        // Get countryId from Germini
        $response = "";
        $url = $url_base . '/api/Country';

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: text/plain'
        ),
        ));

        $response = curl_exec($curl);
        $resultado = json_decode($response);

        foreach ($resultado as $res){
            if ($res->name == $countryName){
                $countryId = $res->id;
            }
        }

        curl_close($curl);
        ///////////////////////////////

        // Get stateId from Germini
        $response = "";
        $url = $url_base . '/api/State?countryId=' . $countryId;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: text/plain'
        ),
        ));

        $response = curl_exec($curl);
        $resultado = json_decode($response);

        foreach ($resultado as $res){
            if ($res->abbreviation == $stateId){
                $stateId = $res->id;
            }
        }

        curl_close($curl);
        ///////////////////////////////

        // Get cityId from Germini
        $response = "";
        $url = $url_base . '/api/City?stateId=' . $stateId;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: text/plain'
        ),
        ));

        $response = curl_exec($curl);
        $resultado = json_decode($response);

        foreach ($resultado as $res){
            if ($res->name == $cityId){
                $cityId = $res->id;
            }
        }

        curl_close($curl);
        ///////////////////////////////


        $gender = $gender === "2" ? 'f' : 'm';
        $dob = date("Y-m-d H:i:s", strtotime($dob));

        // Cria usuário no Germini
        $response = "";
        $url = $url_base . '/api/Consumer/Register';
        $params = [
            "name" => $firstname,
            "cpf" => $cpf,
            "email" => $email,
            "password" => $password,
            "confirmPassword" => $password_confirmation,
            "gender" => $gender,
            "dateOfBirth" => $dob,
            "phoneNumber2" => $phone2,
            "address" => [
                "addressType" => 1,
                "location" => $location,
                "district" => $district,
                "number" => $number,
                "zipCode" => $zipCode,
                "stateId" => $stateId,
                "cityId" => $cityId,
                "countryId" => $countryId
            ]
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

        if (!empty($resultado->errors) || $response == ''){
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
        return $proceed();
    }
}
