<?php

namespace Vexpro\Autenticacao\Block\Order;

use Magento\Sales\Block\Order\Info as SalesInfo;

class Info extends SalesInfo
{
    /**
     * @var string
     */
    protected $_template = 'Vexpro_Autenticacao::order/info.phtml';
}
