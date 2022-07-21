<?php declare(strict_types=1);

namespace Blue\Express\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Blue\Express\Helper\Data as HelperBX;

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
    protected $_token; 

    /**
     * @var HelperBX
     */
    protected $_helper;

    /**
     * Webservice constructor. 
     * @param CheckoutSession $checkoutSession
     * @param HelperBX $helperBX
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(  
        HelperBX $helperBX,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) { 
        $this->_helper = $helperBX;
        $this->curl = $curl;

        $this->_clientaccount = $helperBX->getClientAccount();
        $this->_usercode = $helperBX->getUserCode();
        $this->_token = $helperBX->getToken();
    }
 
    /**
     * @param array $shippingParams
     * @return array
     */
    public function getBXCosto($shippingParams)
    {
            $headers = [
                "Content-Type" => "application/json",
                "Accept" => "application/json",
                "BX-CLIENT_ACCOUNT" => "{$this->_clientaccount}",
                "BX-USERCODE" => "{$this->_usercode}",
                "BX-TOKEN" => "{$this->_token}"
            ];
            $this->curl->setHeaders($headers); 
            $this->curl->post("{$this->apiUrl}", json_encode($shippingParams)); 
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