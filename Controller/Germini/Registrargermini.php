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

class Registrargermini extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $scopeConfig;
    protected $resultRedirectFactory;
    protected $messageManager;
    protected $regionFactory;
    protected $countryFactory;

    public function __construct(
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

        // $firstname = $subject->getRequest()->getParam('firstname');
        // $lastname = $subject->getRequest()->getParam('lastname');

        $fullName = "{$firstname} {$lastname}";

        // $nasc = $subject->getRequest()->getParam('dob');
        $nasc = $customer->getDob();

        $dob = date("Y-m-d H:i:s", strtotime($nasc));
        $dob2 = date("Y-m-d", strtotime($nasc));

        // $genero = $subject->getRequest()->getParam('gender');
        $genero = $customer->getGender();
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

        if ($user_fidelidade == 0)
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

            //convert the XML result into array
            $array_data = json_decode(json_encode(simplexml_load_string($data)), true);

            $logger->info("Resposta SAP: " .$data);
            print_r('<pre>');
            print_r($array_data);
            print_r('</pre>');

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
    }
}
