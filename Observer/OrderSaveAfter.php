<?php declare(strict_types=1);

namespace Blue\Express\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use Blue\Express\Model\Blueservice;
use Blue\Express\Helper\Data as HelperBX;

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
         * Envio los datos de conexion a Blue Express
         */
        $orderID        = $observer->getEvent()->getOrder()->getId();
        $incrementId    = $observer->getEvent()->getOrder()->getIncrementId();
        $status         = $observer->getEvent()->getOrder()->getStatus();
        $state          = $observer->getEvent()->getOrder()->getState();
	$weight_uni 	= $this->_weigthStore;

        /**
         * OBTENDO EL DETALLE DE LA ORDEN
         */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orders = $objectManager->create('Magento\Sales\Model\Order')->load($orderID);
        $detailOrder = $orders->getData();
	$shipping = $orders->getShippingAddress();
      If($shipping && $shipping->getEntityId()){
        $shippingAddress = [
            "entity_id"=> $shipping->getEntityID(),
            "parent_id"=> $shipping->getParentId(),
            "quote_address_id"=> $shipping->getQuoteAddressId(),
            "region_id"=> $shipping->getRegionId(),
            "region"=> $shipping->getRegion(),
            "postcode"=> $shipping->getPostCode(),
            "lastname"=> $shipping->getLastname(),
            "street"=> $shipping->getStreet(),
            "city"=> $shipping->getCity(),
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
            "city"=> $billing->getCity(),
            "email"=> $billing->getEmail(),
            "telephone"=> $billing->getTelephone(),
            "country_id"=> $billing->getCountryId(),
            "firstname"=> $billing->getFirstname(),
            "address_type"=> $billing->getAddressType(),
            "company"=> $billing->getCompany()
        ];
    }
	$ProductDetail = array();
	    foreach( $orders->getAllVisibleItems() as $item ) {
	        $ProductDetail[] = $item->getData();
	    }

        /**
         * OBTENDO LA BASE URL DE LA TIENDA
         */
        $storeManager = $this->_storeManager;
        $baseUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);

        /**
         * ME CONECTO AL SERVICIO PARA ENVIAR POR EL WEBHOOK
         */
        $blueservice    = $this->_blueservice;

        if($state == 'processing' && ( $detailOrder['shipping_method'] =='bxexpress_bxexpress' || $detailOrder['shipping_method'] =='bxprioritario_bxprioritario' || $detailOrder['shipping_method'] == 'bxPremium_bxPremium' )) {
            $pedido = [
                    "OrderId"       => $orderID,
                    "IncrementId"   => $incrementId,
		    "tipoPeso"      => $weight_uni,
                    "DetailOrder"   => $detailOrder,
		    "Shipping" 	    => $shippingAddress,
		    "Billing"	    => $billingAddress,
		    "Product" 	    => $ProductDetail,
		    "Origin"=>[
			"Account" => $baseUrl
		    ]
                ];

	    $this->logger->info('Información enviada al webhook',['Detalle' => $pedido]);
            $respuestaWebhook = $blueservice->getBXOrder($pedido);
            //$this->logger->info('Send order Bx',['Id de la orden' => $orderID,'enviado al webhook'=>$pedido]);
       }
    }
}
