<?php
namespace Vexpro\Autenticacao\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\RegionFactory;

class EditCustomer implements \Magento\Framework\Event\ObserverInterface
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
        // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customer = $observer->getCustomer();
        $customerId = $customer->getId();

        if (isset($customerId)) {
            if ($customerId) {
                $customer = $this->customerRepository->getById($customerId);
                $addresses = $customer->getAddresses();
                // $customer_address = $observer->getData('customer_address')->getData();

                $fullName =
                    $customer->getFirstName() . " " . $customer->getLastName();

                $telephone2 = $addresses[0]->getTelephone();
                $city = $addresses[0]->getCity();
                $country_id = $addresses[0]->getCountryId();
                $zipCode = $addresses[0]->getPostCode();
                // $complemento = $customer_address[]
                // $district = $customer_address['']
                $locations = $addresses[0]->getStreet();
                // $locations = explode("\n", $location);

                $location = isset($locations[0]) ? $locations[0] : "";
                $number = isset($locations[1]) ? $locations[1] : "";
                $district = isset($locations[2]) ? $locations[2] : "";

                $region_id = $addresses[0]->getRegionId();
                $region = $this->regionFactory->create()->load($region_id);

                $regionName = $region->getCode();
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

                    $xmlstr = "<?xml version='1.0' standalone='yes'?>
                    <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:urn=\"urn:cvale:i17:014\">
                    <soapenv:Body>
                        <urn:MT_SAP_BP_Req>
                            <Data_BP_req>
                                <cpf>{$cpf_apenas_numeros}</cpf>
                                <name>{$fullName}</name>
                                <phoneNumber2>{$telephone2}</phoneNumber2>
                                <Id_Interface>03</Id_Interface>
                                <DATA_ADRESS>
                                    <address>
                                        <addressType>1</addressType>
                                        <location>{$location}</location>
                                        <number>{$number}</number>
                                        <district>{$district}</district>
                                        <zipcode>{$zipCodeNumbers}</zipcode>
                                        <regio>{$regionName}</regio>
                                        <city>{$city}</city>
                                    </address>
                                </DATA_ADRESS>
                            </Data_BP_req>
                        </urn:MT_SAP_BP_Req>
                    </soapenv:Body>
                    </soapenv:Envelope>";

                    $simplexml = new \SimpleXMLElement($xmlstr);

                    $input_xml = $simplexml->asXML();

                    $logger = $objectManager->create(
                        "\Psr\Log\LoggerInterface"
                    );
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
                    print_r("<pre>");
                    print_r($array_data);
                    print_r("</pre>");
                }
            }
        }
    }
}
