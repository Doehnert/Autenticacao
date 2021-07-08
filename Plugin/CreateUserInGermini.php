<?php
namespace Vexpro\Autenticacao\Plugin;

use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\UrlInterface;
use Magento\Customer\Api\Data\CustomerInterface;

class CreateUserInGermini
{
    protected $regionFactory;
    protected $timezone;
    protected $_curl;
    protected $scopeConfig;
    protected $resultRedirect;
    protected $_countryFactory;
    protected $urlBuilder;

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
        \Magento\Directory\Model\CountryFactory $countryFactory,
        UrlInterface $urlBuilder
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
        $this->urlBuilder = $urlBuilder;
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
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
        $logger = $objectManager->create('\Psr\Log\LoggerInterface');


        $user_fidelidade = $subject->getRequest()->getParam('user_fidelidade');
        $firstname = $subject->getRequest()->getParam('firstname');
        $lastname = $subject->getRequest()->getParam('lastname');

        $fullName = "{$firstname} {$lastname}";

        $nasc = $subject->getRequest()->getParam('dob');

        $dob = date("Y-m-d H:i:s", strtotime($nasc));
        $dob2 = date("Y-m-d", strtotime($nasc));

        $genero = $subject->getRequest()->getParam('gender');
        $email = $subject->getRequest()->getParam('email');
        $telephone = $subject->getRequest()->getParam('telephone');
        $telephone = preg_replace("/[^0-9]/", "",$telephone);
        $telephone2 = $subject->getRequest()->getParam('telephone2');
        $telephone2 = preg_replace("/[^0-9]/", "",$telephone2);

        $password = $subject->getRequest()->getParam('password');
        $password_confirmation = $subject->getRequest()->getParam('password_confirmation');

        $zipCode = $subject->getRequest()->getParam('postcode');

        $location = $subject->getRequest()->getParam('street')[0];
        $number = $subject->getRequest()->getParam('street')[1];
        $district = $subject->getRequest()->getParam('street')[2];
        $complemento = $subject->getRequest()->getParam('complemento');

        $regionId = $subject->getRequest()->getParam('region_id'); //499
        $region = $this->regionFactory->create()->load($regionId);
        $stateId = $region->getCode();
        $countryId = $region->getCountryId();
        $country = $this->_countryFactory->create()->loadByCode($countryId);
        $countryName = $country->getName();
        $cityId = $subject->getRequest()->getParam('city');
        $countryCode = $subject->getRequest()->getParam('country_id');
        $cpf = $subject->getRequest()->getParam('cpf');

        // if ($cpf == "")
        //     return $proceed();

        $cityName = '';
        $regionName = '';

        // Verifica se o cliente já existe no germini
        // caso exista encerra o plugin
        $url_base = $this->scopeConfig->getValue('acessos/general/identity_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $url = $url_base . '/api/Account/ListUsersByLogin/'. preg_replace("/[^0-9]/", "", $cpf);

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

        if (count($resultado)>0)
        {
            $this->messageManager->addErrorMessage(
                    "Usuário com esse CPF já existe no CVale Fidelidade. Efetue o Login"
                );
            $params = array('cpf' => $cpf);
            return $this->resultRedirectFactory->create()->setPath('customer/account/login', $params);
        }

        /** @var \Magento\Framework\App\RequestInterface $request */

        $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);


        // Get countryId from Germini
        // $response = "";
        // $url = $url_base . '/api/Country';

        // $curl = curl_init();

        // curl_setopt_array($curl, array(
        // CURLOPT_URL => $url,
        // CURLOPT_RETURNTRANSFER => true,
        // CURLOPT_ENCODING => '',
        // CURLOPT_MAXREDIRS => 10,
        // CURLOPT_TIMEOUT => 0,
        // CURLOPT_FOLLOWLOCATION => true,
        // CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        // CURLOPT_CUSTOMREQUEST => 'GET',
        // CURLOPT_HTTPHEADER => array(
        //     'Accept: text/plain'
        // ),
        // ));

        // $response = curl_exec($curl);
        // $resultado = json_decode($response);

        // foreach ($resultado as $res){
        //     if ($res->code == $countryCode){
        //         $countryId = $res->id;
        //     }
        // }

        $countryId = "20b32dbd-8bda-4563-bcd5-0a7e827fc5e4";
        // $countryId = "f24483f2-066c-4fb1-afe3-7aba3df29c00";

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
                $regionName = $res->abbreviation;
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
                $cityName = $res->name;
                $cityId = $res->id;
            }
        }

        curl_close($curl);


        // Caso o usuário não queira participar do programa
        // cria o usuário no SAP

        if (1==1)
        {

            $zipCodeNumbers = preg_replace("/[^0-9]/", "", $zipCode);
            $generoMaiusculo = $genero == 1 ? 'M' : 'F';

            $xmlstr =
            "<?xml version='1.0' standalone='yes'?>
            <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:urn=\"urn:cvale:i17:014\">
            <soapenv:Body>
                <urn:MT_SAP_BP_Req>
                    <Data_BP_req>
                        <cpf>{$cpf}</cpf>
                        <dateOfBirth>{$dob2}</dateOfBirth>
                        <email>{$email}</email>
                        <gender>{$generoMaiusculo}</gender>
                        <name>{$fullName}</name>
                        <nickname>{$firstname}</nickname>
                        <phoneNumber>{$telephone2}</phoneNumber>
                        <phoneNumber2>{$telephone}</phoneNumber2>
                        <Id_Interface>02</Id_Interface>
                        <DATA_ADRESS>
                            <address>
                                <aditionalInfo>{$complemento}</aditionalInfo>
                                <addressType>1</addressType>
                                <district>{$district}</district>
                                <location>{$location}</location>
                                <number>{$number}</number>
                                <zipcode>{$zipCodeNumbers}</zipcode>
                                <regio>{$regionName}</regio>
                                <city>{$cityName}</city>
                            </address>
                        </DATA_ADRESS>
                    </Data_BP_req>
                </urn:MT_SAP_BP_Req>
            </soapenv:Body>
            </soapenv:Envelope>";

            $simplexml = new \SimpleXMLElement($xmlstr);

            $input_xml = $simplexml->asXML();

            $logger->info("Enviado ao SAP: " .$input_xml);

            $sap_url = $this->scopeConfig->getValue('acessos/general/sap_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            // Inicializa um multi-curl handle
            // $mch = curl_multi_init();

            //setting the curl parameters.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $sap_url);
            // Following line is compulsary to add as it is:
            curl_setopt($ch, CURLOPT_POSTFIELDS, $input_xml);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Basic cGlfZ2VybWluaTpjdmFsZTIwMTQ=',
                'Content-Type: text/xml'
            ));
            curl_setopt($ch, CURLOPT_POST, 1);

            $data = curl_exec($ch);
            curl_close($ch);

            // // Adiciona a requisição $ch ao multi-curl handle $mch.
            // curl_multi_add_handle($mch, $ch);

            // // Executa requisição multi-curl e retorna imediatamente.
            // curl_multi_exec($mch, $active);

            // // Acessa as respostas das requisições
            // $data = curl_multi_getcontent($ch);

            //convert the XML result into array
            $array_data = json_decode(json_encode(simplexml_load_string($data)), true);

            $logger->info("Resposta SAP: " .$data);
            print_r('<pre>');
            print_r($array_data);
            print_r('</pre>');

            $customerSession->setFidelity(0);

        }

        if ($user_fidelidade == 0)
        {
            return $proceed();
        }

        $germiniGenero = $genero == 1 ? 'm' : 'f';
        // Cria usuário no Germini
        $response = "";
        $url = $url_base . '/api/Consumer/Register';
        $params = [
            "name" => $firstname,
            "cpf" => $cpf,
            "dateOfBirth" => $dob,
            "gender" => $germiniGenero,
            "email" => $email,
            "password" => $password,
            "confirmPassword" => $password_confirmation,
            "phoneNumber" => $telephone2,
            "phoneNumber2" => $telephone,
            "address" => [
                "addressType" => 1,
                "location" => $location,
                "district" => $district,
                "number" => $number,
                "aditionalInfo" => $complemento,
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

        if (!empty($resultado->errors) || $response == '' || $resultado->success != True){
            foreach ($resultado->errors as $error){
                $this->messageManager->addErrorMessage(
                    $error->message
                );
            }
            return $proceed;
            // return $this->resultRedirectFactory->create()
            // ->setPath(
            //     'customer/account/create'
            // );
        }


        // $this->messageManager->addComplexNoticeMessage(
        //     'customerNeedValidateGermini',
        //     [
        //         'url' => 'https://cvale-fidelidade-consumer.azurewebsites.net/auth/login',
        //     ]
        // );



        $customerSession->setFidelity(2);

        return $proceed();
        // $params = array('cpf' => $cpf);
        // return $this->resultRedirectFactory->create()->setPath('customer/account/login', $params);
    }
}
