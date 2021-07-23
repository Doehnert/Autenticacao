<?php

namespace Vexpro\Autenticacao\Test\Unit\Helper;

use Vexpro\Autenticacao\Helper\Curl as CurlHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;

class CurlTest extends TestCase
{
    protected $identityUrl = 'https://test.com';
    protected $scopeConfigMock;


    public function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMockForAbstractClass();
        $this->scopeConfigMock->expects($this->any())->method('getValue')->with(CurlHelper::XML_PATH_IDENTITY)->willReturn($this->identityUrl);

        // Context Mock
        $this->contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        // Curl Helper Mock
        $this->curlHelperMock = $this->getMockBuilder(CurlHelper::class)->setConstructorArgs([
            'context' => $this->contextMock,
            'scopeConfig' => $this->scopeConfigMock,
        ])->getMock();
    }

    public function testIdentityUrlIsDefinedInConfigg()
    {
        //     $curlHelper = new CurlHelper($this->contextMock, $this->scopeConfigMock);

        //     $this->assertEquals($this->identityUrl, $curlHelper->getIdentityUrl());
    }
}
