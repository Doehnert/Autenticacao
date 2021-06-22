<?php
namespace Vexpro\Autenticacao\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\RegionFactory;

class CustomerData implements \Magento\Framework\Event\ObserverInterface
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
            $myEventData = $observer->getData('myEventData');
            $customer_address = $observer->getData('customer_address')->getData();

            $fullName = $customer_address['firstname'] . ' ' . $customer_address['lastname'];

            $telephone2 = $customer_address['telephone'];
            $city = $customer_address['city'];
            $country_id = $customer_address['country_id'];
            $zipCode = $customer_address['postcode'];
            // $complemento = $customer_address[]
            // $district = $customer_address['']
            $location = $customer_address['street'];
            $locations = explode("\n", $location);

            $location = $locations[0];
            $number = $locations[1];
            $district = $locations[2];

            $zipCodeNumbers = $customer_address['postcode'];
            $region_id = $customer_address['region_id'];
            $region = $this->regionFactory->create()->load($region_id);

            $regionName = $region->getCode();
            $cityName = $customer_address['city'];

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerSession = $objectManager->create('Magento\Customer\Model\Session');
            $customer = $customerSession->getCustomer();
            $customerId = $customer->getId();

            if ($customerId != null)
            {
                $fidelity = $customerSession->getFidelity();

                $customer = $this->customerRepository->getById($customerId);
                $cpfCliente = $customer->getCustomAttribute('cpf')->getValue();
                $cpf_apenas_numeros = preg_replace("/[^0-9]/", "", $cpfCliente);

                if ($fidelity == null || $fidelity == 0)
                {

                    $zipCodeNumbers = preg_replace("/[^0-9]/", "", $zipCode);
                    $generoMaiusculo = $genero == '1' ? 'M' : 'F';

                    $xmlstr =
                    "<?xml version='1.0' standalone='yes'?>
                    <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:urn=\"urn:cvale:i17:014\">
                    <soapenv:Body>
                        <urn:MT_SAP_BP_Req>
                            <Data_BP_req>
                                <cpf>{$cpf_apenas_numeros}</cpf>
                                <name>{$fullName}</name>
                                <phoneNumber2>{$telephone2}</phoneNumber2>
                                <Id_Interface>02</Id_Interface>
                                <DATA_ADRESS>
                                    <address>
                                        <addressType>1</addressType>
                                        <location>{$location}</location>
                                        <number>{$number}</number>
                                        <district>{$district}</district>
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

                    $logger = $objectManager->create('\Psr\Log\LoggerInterface');
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
            }

		}
}
