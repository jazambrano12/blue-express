<?php declare(strict_types=1);

namespace BlueExpress\ShippingBX\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use BlueExpress\ShippingBX\Model\Blueservice;
use BlueExpress\ShippingBX\Helper\Data as HelperBX;

class OrderSaveAfter implements ObserverInterface
{
    /**
    * @var
    */
    protected $_blueservice;

    /**
     * @var string
     */
    protected $_weigthStore;

    protected $logger;

    /* @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     *
     * @param Blueservice $blueservice
     *
     */
    public function __construct(
	\Magento\Store\Model\StoreManagerInterface $storeManager,
        Blueservice $blueservice,
	    HelperBX $helperBX,
        LoggerInterface $logger
    ){
        $this->_storeManager = $storeManager;
        $this->_blueservice = $blueservice;
	    $this->_weigthStore = $helperBX->getWeightUnit();
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        /**
         * I send the connection data to Blue Express
         */
        $orderID        = $observer->getEvent()->getOrder()->getId();
        $incrementId    = $observer->getEvent()->getOrder()->getIncrementId();
        $status         = $observer->getEvent()->getOrder()->getStatus();
        $state          = $observer->getEvent()->getOrder()->getState();
	    $weight_uni 	= $this->_weigthStore;

	 /**
         * I CONNECT TO THE SERVICE TO SEND THROUGH THE WEBHOOK
         */
        	$blueservice    = $this->_blueservice;

        /**
         * OBTAINING THE DETAIL OF THE ORDER
         */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orders = $objectManager->create('Magento\Sales\Model\Order')->load($orderID);
        $detailOrder = $orders->getData();
        $shipping = $orders->getShippingAddress();
        $shippingAddress = [];
        $billingAddress = [];

        If(isset($shipping) && $shipping->getEntityId()){

		    $comuna = $blueservice->eliminarAcentos($shipping->getCity());
            $destricCity = $blueservice->getGeolocation("{$comuna}");
            if(isset($destricCity)){
                $ShipiDistrict = $destricCity['districtCode'];
                $shipiRegion = $destricCity['regionCode'];
            }

            $billing = $orders->getBillingAddress();
            $comunaBilling = $blueservice->eliminarAcentos($billing->getCity());
            $destricBillingCity = $blueservice->getGeolocation("{$comunaBilling}");
            if(isset($destricCity)){
                    $billiDistrict = $destricBillingCity['districtCode'];
                    $billiRegion = $destricBillingCity['regionCode'];
            }

            $shippingAddress = [
                "entity_id"=> $shipping->getEntityID(),
                "parent_id"=> $shipping->getParentId(),
                "quote_address_id"=> $shipping->getQuoteAddressId(),
                "region_id"=> $shipping->getRegionId(),
                "region"=> $shipping->getRegion(),
                "postcode"=> $shipping->getPostCode(),
                "lastname"=> $shipping->getLastname(),
                "street"=> $shipping->getStreet(),
                "city"=> $blueservice->eliminarAcentos($shipping->getCity()),
		        "district" => $ShipiDistrict,
                "state" => $shipiRegion,
                "email"=> $shipping->getEmail(),
                "telephone"=> $shipping->getTelephone(),
                "country_id"=> $shipping->getCountryId(),
                "firstname"=> $shipping->getFirstname(),
                "address_type"=> $shipping->getAddressType(),
                "company"=> $shipping->getCompany()
            ];

	        $billing = $orders->getBillingAddress();
            $billingAddress = [
                "entity_id"=> $billing->getEntityID(),
                "parent_id"=> $billing->getParentId(),
                "quote_address_id"=> $billing->getQuoteAddressId(),
                "region_id"=> $billing->getRegionId(),
                "region"=> $billing->getRegion(),
                "postcode"=> $billing->getPostCode(),
                "lastname"=> $billing->getLastname(),
                "street"=> $billing->getStreet(),
                "city"=> $blueservice->eliminarAcentos($billing->getCity()),
		        "district" => $billiDistrict,
                "state" => $billiRegion,
                "email"=> $billing->getEmail(),
                "telephone"=> $billing->getTelephone(),
                "country_id"=> $billing->getCountryId(),
                "firstname"=> $billing->getFirstname(),
                "address_type"=> $billing->getAddressType(),
                "company"=> $billing->getCompany()
            ];
        }

	    $ProductDetail = array();
	    $dimensiones = array();
	    $objectManager  = \Magento\Framework\App\ObjectManager::getInstance();
        $resource       = $objectManager->get(\Magento\Catalog\Model\ResourceModel\Product::class);

	    foreach( $orders->getAllVisibleItems() as $item ) {

	        $ProductDetail[]    = $item->getData();
		    $alto               = $resource->getAttributeRawValue($resource->getIdBySku($item->getSku()), 'alto', 0);
		    $ancho              = $resource->getAttributeRawValue($resource->getIdBySku($item->getSku()), 'ancho', 0);
            $largo              = $resource->getAttributeRawValue($resource->getIdBySku($item->getSku()), 'largo', 0);

            if(empty($alto) || $alto == 0){
                $alto  = 10;
            }

		    if(empty($ancho) || $ancho == 0){
                $ancho  = 10;
            }

		    if(empty($largo) || $largo == 0){
                $largo  = 10;
            }

            $dimensiones[]  = [
                "height"	=> $alto,
                "width"		=> $ancho,
                "large"		=> $largo
           	];
	    }

        /**
         * GETTING THE STORE URL BASE
         */
        $storeManager = $this->_storeManager;
        $baseUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);

        if($state == 'processing' && ( $detailOrder['shipping_method'] =='bxexpress_bxexpress' || $detailOrder['shipping_method'] =='bxprioritario_bxprioritario' || $detailOrder['shipping_method'] == 'bxPremium_bxPremium' || $detailOrder['shipping_method'] == 'bxsameday_bxsameday')) {
	        $typeCarrier = '';
            switch ($detailOrder['shipping_method']){
                case 'bxexpress_bxexpress':
                    $typeCarrier = "EX";
                    break;
                case 'bxprioritario_bxprioritario':
                    $typeCarrier = "PY";
                    break;
		        case 'bxsameday_bxsameday':
                    $typeCarrier = "MD";
                    break;
                default:
                $typeCarrier = "";
            }

            $pedido = [
                    "OrderId"       => $orderID,
                    "IncrementId"   => $incrementId,
                    "tipoPeso"      => $weight_uni,
                    "DetailOrder"   => $detailOrder,
                    "Shipping" 	    => $shippingAddress,
                    "Billing"	    => $billingAddress,
		            "type_carrier"  => $typeCarrier,
                    "Product" 	    => $ProductDetail,
		            "dimensions"    => $dimensiones,
                    "Origin"=>[
                        "Account" => $baseUrl
                    ]
            ];

	        $this->logger->info('Information sent to the webhook',['Detalle' => $destricCity]);
            $respuestaWebhook = $blueservice->getBXOrder($pedido);
       }
    }
}

