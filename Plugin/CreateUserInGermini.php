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
    ) {
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
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get(
            "\Magento\Customer\Model\Session"
        );
        $logger = $objectManager->create("\Psr\Log\LoggerInterface");

        $associated = $subject->getRequest()->getParam("associated");

        $user_fidelidade = $subject->getRequest()->getParam("user_fidelidade");

        $email = $subject->getRequest()->getParam("email");
        $telephone = $subject->getRequest()->getParam("telephone");
        $telephone = preg_replace("/[^0-9]/", "", $telephone);
        $telephone2 = $subject->getRequest()->getParam("telephone2");
        $telephone2 = preg_replace("/[^0-9]/", "", $telephone2);

        $password = $subject->getRequest()->getParam("password");
        $password_confirmation = $subject
            ->getRequest()
            ->getParam("password_confirmation");

        $cpf = $subject->getRequest()->getParam("cpf");

        $cityName = "";
        $regionName = "";

        $genero = $subject->getRequest()->getParam("gender");

        $stateId = '';
        if ($associated == 0) {

            $firstname = $subject->getRequest()->getParam("firstname");
            $lastname = $subject->getRequest()->getParam("lastname");

            $fullName = "{$firstname} {$lastname}";

            $nasc = $subject->getRequest()->getParam("dob");
            $nasc = str_replace("/", "-", $nasc);

            $dob = date("Y-m-d H:i:s", strtotime($nasc));
            $dob2 = date("Y-m-d", strtotime($nasc));

            $zipCode = $subject->getRequest()->getParam("postcode");

            $location = $subject->getRequest()->getParam("street")[0];
            $number = $subject->getRequest()->getParam("street")[1];
            $district = $subject->getRequest()->getParam("street")[2];
            $complemento = $subject->getRequest()->getParam("complemento");

            $regionId = $subject->getRequest()->getParam("region_id"); //499
            $region = $this->regionFactory->create()->load($regionId);
            $stateId = $region->getCode();
            $countryId = $region->getCountryId();
            $country = $this->_countryFactory->create()->loadByCode($countryId);
            $countryName = $country->getName();
            $cityId = $subject->getRequest()->getParam("city");
            $countryCode = $subject->getRequest()->getParam("country_id");

            // Verifica se o cliente já existe no germini
            // caso exista encerra o plugin
            $url_base = $this->scopeConfig->getValue(
                "acessos/general/identity_url",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $url =
                $url_base .
                "/api/Account/ListUsersByLogin/" .
                preg_replace("/[^0-9]/", "", $cpf);

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => ["Accept: text/plain"],
            ]);

            $response = curl_exec($curl);
            $resultado = json_decode($response);

            if (count($resultado) > 0) {
                $this->messageManager->addErrorMessage(
                    "Usuário com esse CPF já existe no CVale Fidelidade. Efetue o Login"
                );
                $params = ["cpf" => $cpf];
                return $this->resultRedirectFactory
                    ->create()
                    ->setPath("customer/account/login", $params);
            }

            /** @var \Magento\Framework\App\RequestInterface $request */

            $url_base = $this->scopeConfig->getValue(
                "acessos/general/kernel_url",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            // Get countryId from Germini
            // $response = "";
            // $url = $url_base . '/api/Country';

            // $curl = curl_init();

            // curl_setopt_array($curl, array(
            //     CURLOPT_URL => $url,
            //     CURLOPT_RETURNTRANSFER => true,
            //     CURLOPT_ENCODING => '',
            //     CURLOPT_MAXREDIRS => 10,
            //     CURLOPT_TIMEOUT => 0,
            //     CURLOPT_FOLLOWLOCATION => true,
            //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //     CURLOPT_CUSTOMREQUEST => 'GET',
            //     CURLOPT_HTTPHEADER => array(
            //         'Accept: text/plain'
            //     ),
            // ));

            // $response = curl_exec($curl);
            // $resultado = json_decode($response);

            // foreach ($resultado as $res) {
            //     if ($res->code == $countryCode) {
            //         $countryId = $res->id;
            //     }
            // }

            $countryId = "20b32dbd-8bda-4563-bcd5-0a7e827fc5e4";
            // $countryId = "f24483f2-066c-4fb1-afe3-7aba3df29c00";

            curl_close($curl);
            ///////////////////////////////

            // Get stateId from Germini
            $response = "";
            $url = $url_base . "/api/State?countryId=" . $countryId;

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => ["Accept: text/plain"],
            ]);

            $response = curl_exec($curl);
            $resultado = json_decode($response);

            foreach ($resultado as $res) {
                if ($res->abbreviation == $stateId) {
                    $regionName = $res->abbreviation;
                    $stateId = $res->id;
                }
            }

            curl_close($curl);
            ///////////////////////////////

            // Get cityId from Germini
            $response = "";
            $url = $url_base . "/api/City?stateId=" . $stateId;

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => ["Accept: text/plain"],
            ]);

            $response = curl_exec($curl);
            $resultado = json_decode($response);

            foreach ($resultado as $res) {
                if ($res->name == $cityId) {
                    $cityName = $res->name;
                    $cityId = $res->id;
                }
            }

            curl_close($curl);

            // Caso o usuário não queira participar do programa
            // cria o usuário no SAP caso ainda nao seja associado

            $zipCodeNumbers = preg_replace("/[^0-9]/", "", $zipCode);

            switch ($genero) {
                case 1:
                    $generoMaiusculo = "M";
                    break;
                case 2:
                    $generoMaiusculo = "F";
                    break;
                case 3:
                    $generoMaiusculo = "NDA";
                    break;
                default:
                    $generoMaiusculo = "NDA";
            }
            // $generoMaiusculo = $genero == 1 ? "M" : "F";

            $xmlstr = "<?xml version='1.0' standalone='yes'?>
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
                        <Id_Interface>03</Id_Interface>
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
                            <address_ship>
                                <aditionalInfo>{$complemento}</aditionalInfo>
                                <addressType>1</addressType>
                                <district>{$district}</district>
                                <location>{$location}</location>
                                <number>{$number}</number>
                                <zipcode>{$zipCodeNumbers}</zipcode>
                                <regio>{$regionName}</regio>
                                <city>{$cityName}</city>
                            </address_ship>
                        </DATA_ADRESS>
                    </Data_BP_req>
                </urn:MT_SAP_BP_Req>
            </soapenv:Body>
            </soapenv:Envelope>";

            $simplexml = new \SimpleXMLElement($xmlstr);

            $input_xml = $simplexml->asXML();

            $logger->info("Enviado ao SAP: " . $input_xml);

            $sap_url = $this->scopeConfig->getValue(
                "acessos/general/sap_url",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            //setting the curl parameters.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $sap_url);
            // Following line is compulsary to add as it is:
            curl_setopt($ch, CURLOPT_POSTFIELDS, $input_xml);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);


            // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Basic cGlfZ2VybWluaTpjdmFsZTIwMTQ=",
                "Content-Type: text/xml",
            ]);
            curl_setopt($ch, CURLOPT_POST, 1);

            $data = curl_exec($ch);
            curl_close($ch);

            //convert the XML result into array
            $array_data = json_decode(
                json_encode(simplexml_load_string($data)),
                true
            );

            $logger->info("Resposta SAP: " . $data);

            $customerSession->setFidelity(0);

            // if ($data == '') {
            //     $this->messageManager->addErrorMessage("Ocorreu uma falha no cadastro. Tente novamente!");
            //     return $this->resultRedirectFactory->create()
            //         ->setPath(
            //             'customer/account/create'
            //         );
            // }
        }

        switch ($genero) {
            case 1:
                $germiniGenero = "m";;
                break;
            case 2:
                $germiniGenero = "f";;
                break;
            case 3:
                $germiniGenero = "nda";;
                break;
            default:
                $germiniGenero = "nda";;
                break;
        }

        //************ */

        if ($user_fidelidade == 0) {
            return $proceed();
        }


        // $germiniGenero = $genero == 1 ? "m" : "f";
        // Cria usuário no Germini
        $response = "";
        $url_base = $this->scopeConfig->getValue(
            "acessos/general/kernel_url",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $url = $url_base . "/api/Consumer/Register";

        if ($associated == 0) {
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
                    "countryId" => $countryId,
                ],
            ];
        } else {
            $params = [
                "cpf" => $cpf,
                "gender" => $germiniGenero,
                "email" => $email,
                "password" => $password,
                "confirmPassword" => $password_confirmation,
                "phoneNumber" => $telephone,
                "phoneNumber2" => $telephone2,
                "associated" => true,
                "address" => ''
            ];
        }




        $data_json = json_encode($params);
        $logger->info("Enviado ao Germini: " . json_encode($params));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Accept: text/plain",
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);
        $resultado = json_decode($response);

        $logger->info("Resposta do Germini: " . json_encode($resultado));

        if (
            $response == ""
        ) {
            if (isset($resultado->errors)) {
                foreach ($resultado->errors as $error) {
                    $this->messageManager->addErrorMessage($error->message);
                }
            }

            return $proceed;
            // return $this->resultRedirectFactory->create()
            //     ->setPath(
            //         'customer/account/create'
            //     );
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

    public function afterExecute(
        \Magento\Customer\Controller\Account\CreatePost $subject,
        $result
    ) {
        $result->setPath('/');
        return $result;
    }

    public function beforeExecute(
        \Magento\Customer\Controller\Account\CreatePost $subject
    ) {

        $associated = $subject->getRequest()->getParam("associated");

        if ($associated == 1) {
            // Pegos os dados na API VerifyDocumentNoMask
            $cpf = $subject->getRequest()->getParam("cpf");
            // VERIFICA SE EXISTE ESSE CPF NO BP DO SAP
            $url_base = $this->scopeConfig->getValue('acessos/general/kernel_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $url = $url_base . "/api/Consumer/VerifyDocumentNoMask?cpfCnpj={$cpf}&maskData=true";

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
            $cliente = json_decode($response);

            $names = explode(" ", ltrim($cliente->data->name));
            $last_name = $names;
            array_shift($last_name);
            $last_name = join(" ", $last_name);
            $first_name = isset($names[0]) ? $names[0] : '';

            $subject->getRequest()->setPostValue("firstname", $first_name);
            $subject->getRequest()->setPostValue("lastname", $last_name);
            $subject->getRequest()->setPostValue("dob", date("d/m/Y", strtotime($cliente->data->dateOfBirth)));
            $subject->getRequest()->setPostValue("postcode", $cliente->data->address->zipCode);
            $street = [];
            array_push($street, $cliente->data->address->location);
            array_push($street, $cliente->data->address->number);
            array_push($street, $cliente->data->address->district);
            $subject->getRequest()->setPostValue("street", $street);

            $subject->getRequest()->setPostValue("email", $cliente->data->email);
            $phoneNumber = $cliente->data->phoneNumber == '' ? $cliente->data->phoneNumber2 : $cliente->data->phoneNumber;
            $phoneNumber2 = $cliente->data->phoneNumber2 == '' ? $cliente->data->phoneNumber : $cliente->data->phoneNumber2;
            $subject->getRequest()->setPostValue("telephone", $phoneNumber);
            $subject->getRequest()->setPostValue("telephone2", $phoneNumber2);


            // CIDADE E ESTADO
            $subject->getRequest()->setPostValue("city", $cliente->data->address->city->name);

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $region = $objectManager->create('Magento\Directory\Model\Region')
                ->loadByCode($cliente->data->address->state->abbreviation, 'BR');
            $region_id = $region->getData()['region_id'];

            $subject->getRequest()->setPostValue("region_id", $region_id);
        }
    }
}
