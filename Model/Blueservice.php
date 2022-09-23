<?php declare(strict_types=1);

namespace Blue\Express\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Blue\Express\Helper\Data as HelperBX;
use Psr\Log\LoggerInterface;

class Blueservice

{
    /**
     * Get country path
     */
    const GEOLOCATION_URL = 'https://bx-tracking.bluex.cl/bx-geo/states';

     /**
     * @var string
     */
    protected $apiUrl =  'https://bx-tracking.bluex.cl/bx-pricing/v1';

    /**
     * @var string
     */
    protected $_clientaccount;

    /**
     * @var string
     */
    protected $_usercode;

    /**
     * @var string
     */
    protected $_bxapiKey;

    /**
     * @var string
     */
    protected $_token;

    /**
     * @var string
     */
    protected $_webhook;

    /**
     * @var string
     */
    protected $_keywebhook;

    /**
     * @var HelperBX
     */
    protected $_helper;

    protected $logger;


    /**
     * Webservice constructor.
     * @param CheckoutSession $checkoutSession
     * @param HelperBX $helperBX
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        HelperBX $helperBX,
        \Magento\Framework\HTTP\Client\Curl $curl,
        LoggerInterface $logger
    ) {
        $this->_helper = $helperBX;
        $this->curl = $curl;
	$this->logger = $logger;

        $this->_clientaccount = $helperBX->getClientAccount();
        $this->_usercode = $helperBX->getUserCode();
	$this->_bxapiKey = $helperBX->getBxapiKey();
        $this->_token = $helperBX->getToken();
	$this->_webhook = $helperBX->getWebHook();
	$this->_keywebhook = $helperBX->getKeyWebHook();
    }

    /**
     * @param mixed $datosParams
     * @return array
     */
    public function getBXOrder($datosParams)
    {
        $headers = [
            "Content-Type" => "application/json",
            "apikey" => "{$this->_keywebhook}"
        ];
        $this->curl->setHeaders($headers);
        $this->curl->post("{$this->_webhook}", json_encode($datosParams));
        $result = $this->curl->getBody();

        return $result;
    }

    /**
     * @param array $shippingParams
     * @return array
     */
    public function getBXCosto($shippingParams)
    {
	    $this->logger->info('Info enviada  al api precio',$shippingParams);

            $headers = [
                "Content-Type" => "application/json",
                "Accept" => "application/json",
                "apikey" => "{$this->_bxapiKey}",
                "BX-TOKEN" => "{$this->_token}"
            ];
            $this->curl->setHeaders($headers);
            $this->curl->post("https://qaapigw.bluex.cl/api/legacy/pricing/v1", json_encode($shippingParams));
            $result = $this->curl->getBody();

        return $result;
    }

    /**
     *
     * @param string $shippingCity
     * @return array
     *
     */
    public function getGeolocation($shippingCity)
    {
        $headers = [
            "Content-Type" => "application/json",
            "Accept" => "application/json",
            "BX-CLIENT_ACCOUNT" => "{$this->_clientaccount}",
            "BX-USERCODE" => "{$this->_usercode}",
            "BX-TOKEN" => "{$this->_token}"
        ];
        $this->curl->setHeaders($headers);
        $this->curl->get(self::GEOLOCATION_URL);

        $result = $this->curl->getBody();

        $tempData = str_replace("\\", "",$result);
        $cleanData = json_decode($tempData,true);
        $data = $cleanData['data'][0]['states'];
        $city =[];

        for($i=0; $i < count($data); $i++){
            for($j=0; $j < count($data[$i]['ciudades']); $j++){
                /**
                 * Buscamo los datos de la comuna seleccionada
                 */
                if( $data[$i]['ciudades'][$j]['name'] == strtoupper($shippingCity) ){
                    $city = array("code"=>$data[$i]['code'],"district"=>$data[$i]['ciudades'][$j]['defaultDistrict']);
                }
            }
        }
          return $city;
    }
}