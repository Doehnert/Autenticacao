<?php

namespace Vexpro\Autenticacao\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\RegionFactory;

class EditCustomerAddress implements \Magento\Framework\Event\ObserverInterface
{
    protected $scopeConfig;
    protected $regionFactory;
    protected $_curl;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        RegionFactory $regionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->customerRepository = $customerRepository;
        $this->regionFactory = $regionFactory;
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customer = $customerSession->getCustomer();
        $customerId = $customer->getId();
        $pontosCliente = 0;
        if (isset($customerId)) {
            $customer = $this->customerRepository->getById($customerId);
            if ($customer->getCustomAttribute('pontos_cliente') != null) {
                if ($customer->getCustomAttribute('pontos_cliente')->getValue() != null) {
                    $pontosCliente = $customer->getCustomAttribute('pontos_cliente')->getValue();
                }
            }
        } else {
            $pontosCliente = null;
        }


        if (isset($customerId)) {
            if ($customerId) {
                $customer = $this->customerRepository->getById($customerId);
                $addresses = $customer->getAddresses();
                $mainAddressId = $customer->getDefaultBilling();
                $shippingAddressId = $customer->getDefaultShipping();

                if ($customer->getCustomAttribute("cpf") !== null) {
                    $cpfCliente = $customer
                        ->getCustomAttribute("cpf")
                        ->getValue();
                } else {
                    $cpfCliente = $customer->getTaxVat();
                }

                $fullName =
                    $customer->getFirstName() . " " . $customer->getLastName();

                $firstName = $customer->getFirstName();

                $genero = '';
                $genero = $customer->getGender();
                $dob2 = '';
                $dob2 = $customer->getDob();
                $email = '';
                $email = $customer->getEmail();

                $address = $observer->getCustomerAddress();

                $changedAddressType = 0;
                if ($address->getId() == $shippingAddressId) {
                    $changedAddressType = 1;
                }

                $flag_same_addres = 0;

                if ($mainAddressId == $shippingAddressId) {
                    $flag_same_addres = 1;
                }

                $telephone2 = $address->getTelephone();
                $city = $address->getCity();
                $country_id = $address->getCountryId();
                $zipCode = $address->getPostcode();
                // $complemento = $customer_address[]
                // $district = $customer_address['']
                $locations = $address->getStreet();
                // $locations = explode("\n", $location);

                $location = isset($locations[0]) ? $locations[0] : "";
                $number = isset($locations[1]) ? $locations[1] : "";
                $district = isset($locations[2]) ? $locations[2] : "";

                $region_id = $address->getRegionId();
                $region = $this->regionFactory->create()->load($region_id);

                $regionName = $region->getCode();
                // $cityName = $customer_address['city'];

                // $fidelity = $customerSession->getFidelity();

                // $customer = $this->customerRepository->getById($customerId);
                if ($customer->getCustomAttribute("cpf") !== null) {
                    $cpfCliente = $customer
                        ->getCustomAttribute("cpf")
                        ->getValue();
                } else {
                    $cpfCliente = $customer->getTaxVat();
                }
                $cpf_apenas_numeros = preg_replace("/[^0-9]/", "", $cpfCliente);

                if (1 == 1) {
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
                    }

                    // $generoMaiusculo = $genero == 1 ? "M" : "F";

                    $addressXml = $changedAddressType == 0 ? "address" : "address_ship";

                    // if ($cpf_apenas_numeros == "") {
                    //     return;
                    // }

                    if ($flag_same_addres == 1) {
                        $xmlstr = "<?xml version='1.0' standalone='yes'?>
                    <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:urn=\"urn:cvale:i17:014\">
                    <soapenv:Body>
                        <urn:MT_SAP_BP_Req>
                            <Data_BP_req>
                                <cpf>{$cpf_apenas_numeros}</cpf>

                                <dateOfBirth>{$dob2}</dateOfBirth>
                                <email>{$email}</email>
                                <gender>{$generoMaiusculo}</gender>

                                <name>{$fullName}</name>

                                <nickname>{$firstName}</nickname>

                                <phoneNumber>{$telephone2}</phoneNumber>
                                <phoneNumber2>{$telephone2}</phoneNumber2>
                                <Id_Interface>03</Id_Interface>
                                <DATA_ADRESS>
                                    <address>
                                        <addressType>1</addressType>
                                        <district>{$district}</district>
                                        <location>{$location}</location>
                                        <number>{$number}</number>
                                        <zipcode>{$zipCodeNumbers}</zipcode>
                                        <regio>{$regionName}</regio>
                                        <city>{$city}</city>
                                    </address>
                                    <address_ship>
                                        <addressType>1</addressType>
                                        <district>{$district}</district>
                                        <location>{$location}</location>
                                        <number>{$number}</number>
                                        <zipcode>{$zipCodeNumbers}</zipcode>
                                        <regio>{$regionName}</regio>
                                        <city>{$city}</city>
                                    </address_ship>
                                </DATA_ADRESS>
                            </Data_BP_req>
                        </urn:MT_SAP_BP_Req>
                    </soapenv:Body>
                    </soapenv:Envelope>";
                    } else {
                        $xmlstr = "<?xml version='1.0' standalone='yes'?>
                        <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:urn=\"urn:cvale:i17:014\">
                        <soapenv:Body>
                            <urn:MT_SAP_BP_Req>
                                <Data_BP_req>
                                    <cpf>{$cpf_apenas_numeros}</cpf>

                                    <dateOfBirth>{$dob2}</dateOfBirth>
                                    <email>{$email}</email>
                                    <gender>{$generoMaiusculo}</gender>

                                    <name>{$fullName}</name>

                                    <nickname>{$firstName}</nickname>

                                    <phoneNumber>{$telephone2}</phoneNumber>
                                    <phoneNumber2>{$telephone2}</phoneNumber2>
                                    <Id_Interface>03</Id_Interface>
                                    <DATA_ADRESS>
                                        <{$addressXml}>
                                            <addressType>1</addressType>
                                            <district>{$district}</district>
                                            <location>{$location}</location>
                                            <number>{$number}</number>
                                            <zipcode>{$zipCodeNumbers}</zipcode>
                                            <regio>{$regionName}</regio>
                                            <city>{$city}</city>
                                        </{$addressXml}>
                                    </DATA_ADRESS>
                                </Data_BP_req>
                            </urn:MT_SAP_BP_Req>
                        </soapenv:Body>
                        </soapenv:Envelope>";
                    }



                    $simplexml = new \SimpleXMLElement($xmlstr);

                    $input_xml = $simplexml->asXML();

                    $logger = $objectManager->create(
                        "\Psr\Log\LoggerInterface"
                    );
                    $logger->info("Enviado ao SAP (EditCustomerAddress): " . $input_xml);

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
                    // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Basic cGlfZ2VybWluaTpjdmFsZTIwMTQ=",
                        "Content-Type: text/xml",
                    ]);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    $data = curl_exec($ch);
                    curl_close($ch);

                    // //convert the XML result into array
                    $array_data = json_decode(
                        json_encode(simplexml_load_string($data)),
                        true
                    );

                    $logger->info("Resposta SAP: " . $data);
                    print_r("<pre>");
                    print_r($array_data);
                    print_r("</pre>");
                }
            }
        }
    }
}
