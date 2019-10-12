<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\ProductBundle\Tests\Seo\Services;

use PHPUnit\Framework\TestCase;
use Sonata\Component\Currency\Currency;
use Sonata\Component\Currency\CurrencyDetectorInterface;
use Sonata\IntlBundle\Templating\Helper\NumberHelper;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Provider\ImageProvider;
use Sonata\MediaBundle\Provider\Pool;
use Sonata\ProductBundle\Entity\BaseProduct;
use Sonata\ProductBundle\Seo\Services\Facebook;
use Sonata\SeoBundle\Seo\SeoPage;
use Sonata\SeoBundle\Twig\Extension\SeoExtension;
use Symfony\Component\Routing\RouterInterface;

class ProductFbMock extends BaseProduct
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
    }

    public function getDescription()
    {
        return 'O-some product';
    }
}

class FacebookTest extends TestCase
{
    public function testAlterPage()
    {
        $mediaPool = $this->createMock(Pool::class);
        $seoPage = new SeoPage('test');
        $extension = new SeoExtension($seoPage, 'UTF-8');
        $numberHelper = $this->createMock(NumberHelper::class);
        $currencyDetector = $this->createMock(CurrencyDetectorInterface::class);
        $product = new ProductFbMock();
        $router = $this->createMock(RouterInterface::class);

        //Prepare currency
        $currency = new Currency();
        $currency->setLabel('EUR');
        $currencyDetector->expects($this->any())
                ->method('getCurrency')
                ->willReturn($currency);

        // Check if the header data are correctly registered
        $fbService = new Facebook($router, $mediaPool, $numberHelper, $currencyDetector, 'test', 'test', 'reference');
        $fbService->alterPage($seoPage, $product);
        $content = $extension->getHeadAttributes();

        $this->assertContains('fb: http://ogp.me/ns/fb#', $content);

        $content = $extension->getMetadatas();

        $this->assertContains('O-some product', $content);
    }
    public function testAlterPageImage()
    {
        $mediaPool = $this->createMock(Pool::class);
        $seoPage = new SeoPage('test');
        $extension = new SeoExtension($seoPage, 'UTF-8');
        $numberHelper = $this->createMock(NumberHelper::class);
        $currencyDetector = $this->createMock(CurrencyDetectorInterface::class);
        $product = new ProductFbMock();
        $router = $this->createMock(RouterInterface::class);

        //Prepare currency
        $currency = new Currency();
        $currency->setLabel('EUR');
        $currencyDetector->expects($this->any())
                ->method('getCurrency')
                ->willReturn($currency);

        $imageDummyLink = 'http://localhost/public/upload/dummy.png';

        // Test getImage
        $image = $this->createMock(MediaInterface::class);

        $imageProvider = $this->createMock(ImageProvider::class);
        $imageProvider->expects($this->any())
            ->method('generatePublicUrl')->willReturn($imageDummyLink);

        $image->expects($this->any())
            ->method('getName')->willReturn('correctMedia');
        $image->expects($this->any())
            ->method('getWidth')->willReturn(1111);
        $image->expects($this->any())
            ->method('getHeight')->willReturn(2222);        
        $image->expects($this->any())
            ->method('getProviderName')->willReturn($imageProvider);
        $image->expects($this->any())
            ->method('getContentType')->willReturn('image/png');

        $mediaPool->expects($this->any())
            ->method('getProvider')->willReturn($imageProvider);

        $product->setImage($image);

        // Check if the header data are correctly registered
        $fbService = new Facebook($router, $mediaPool, $numberHelper, $currencyDetector, 'test', 'test', 'reference');
        $fbService->alterPage($seoPage, $product);
        $content = $extension->getHeadAttributes();

        $this->assertContains('fb: http://ogp.me/ns/fb#', $content);

        $content = $extension->getMetadatas();

        //description
        $this->assertContains('O-some product', $content);
        // image link
        $this->assertContains($imageDummyLink, $content);
        //image width
        $this->assertContains('1111', $content);
        //image height
        $this->assertContains('2222', $content);
        //image content type
        $this->assertContains('image/png', $content);
    }
}
