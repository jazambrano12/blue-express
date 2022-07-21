<?php

namespace Blue\Express\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;  

/**
 * Class Data
 *
 * @package Blue\Express\Helper
 */
class Data extends AbstractHelper
{
    
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;   

     /**
     * Data constructor.
     * @param ScopeConfigInterface $scopeConfig   
     */
    public function __construct( 
        ScopeConfigInterface $scopeConfig
    ) { 
        $this->_scopeConfig  = $scopeConfig; 
    }

    /**
     * @return string
     */
    public function getClientAccount()
    {
        return $this->_scopeConfig->getValue('carriers/bluexpress/clientaccount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getUserCode()
    {
        return $this->_scopeConfig->getValue('carriers/bluexpress/usercode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->_scopeConfig->getValue('carriers/bluexpress/token', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

}