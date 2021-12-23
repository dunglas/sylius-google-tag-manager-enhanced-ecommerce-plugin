<?php

declare(strict_types=1);

namespace Tests\StefanDoorn\SyliusGtmEnhancedEcommercePlugin\EventListener;

use PHPUnit\Framework\TestCase;
use StefanDoorn\SyliusGtmEnhancedEcommercePlugin\EventListener\ThankYouListener;
use StefanDoorn\SyliusGtmEnhancedEcommercePlugin\TagManager\AddTransaction;
use Sylius\Bundle\CoreBundle\Controller\OrderController;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Currency\Context\CurrencyContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Xynnn\GoogleTagManagerBundle\Service\GoogleTagManager;

/**
 * @covers \StefanDoorn\SyliusGtmEnhancedEcommercePlugin\EventListener\ThankYouListener
 */
final class ThankYouListenerTest extends TestCase
{
    public function testWrongController(): void
    {
        // Requirements
        $gtm = new GoogleTagManager(true, 'id1234');

        // Build base mocks
        $channelContext = $this->getMockBuilder(ChannelContextInterface::class)->getMock();
        $currencyContext = $this->getMockBuilder(CurrencyContextInterface::class)->getMock();
        $orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)->getMock();
        $controller = $this->getMockBuilder(OrderController::class)->disableOriginalConstructor()->getMock();
        $kernel = $this->getMockBuilder(KernelInterface::class)->disableOriginalConstructor()->getMock();
        $request = new Request();

        // Create event
        $event = new ControllerEvent($kernel, [$controller, 'indexAction'], $request, HttpKernelInterface::MAIN_REQUEST);

        // Service and listener
        $service = new AddTransaction($gtm, $channelContext, $currencyContext);
        $listener = new ThankYouListener($service, $orderRepository);

        // Run listener
        $listener->onKernelController($event);

        // Check result
        $this->assertArrayNotHasKey('ecommerce', $gtm->getData());
    }

    public function testNoOrderFound(): void
    {
        // Requirements
        $gtm = new GoogleTagManager(true, 'id1234');

        // Build base mocks
        $channelContext = $this->getMockBuilder(ChannelContextInterface::class)->getMock();
        $currencyContext = $this->getMockBuilder(CurrencyContextInterface::class)->getMock();
        $orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)->getMock();
        $controller = $this->getMockBuilder(OrderController::class)->disableOriginalConstructor()->getMock();
        $session = $this->getMockBuilder(SessionInterface::class)->getMock();
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $kernel = $this->getMockBuilder(KernelInterface::class)->disableOriginalConstructor()->getMock();

        // Mock expectations
        $request->expects($this->once())->method('getSession')->willReturn($session);
        $session->expects($this->once())->method('get')->with('sylius_order_id')->willReturn(88);
        $orderRepository->expects($this->once())->method('find')->with(88)->willReturn(null);
        $controller->expects($this->any())->method('thankYouAction')->willReturn(new Response());

        // Create event
        $event = new ControllerEvent($kernel, [$controller, 'thankYouAction'], $request, HttpKernelInterface::MAIN_REQUEST);

        // Service and listener
        $service = new AddTransaction($gtm, $channelContext, $currencyContext);
        $listener = new ThankYouListener($service, $orderRepository);

        // Run listener
        $listener->onKernelController($event);

        // Check result
        $this->assertArrayNotHasKey('ecommerce', $gtm->getData());
    }

    public function testEnvironmentIsAddedToGtmObject(): void
    {
        // Requirements
        $gtm = new GoogleTagManager(true, 'id1234');
        $order = new Order();

        // Build base mocks
        $channelContext = $this->getMockBuilder(ChannelContextInterface::class)->getMock();
        $currencyContext = $this->getMockBuilder(CurrencyContextInterface::class)->getMock();
        $orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)->getMock();
        $controller = $this->getMockBuilder(OrderController::class)->disableOriginalConstructor()->getMock();
        $session = $this->getMockBuilder(SessionInterface::class)->getMock();
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $kernel = $this->getMockBuilder(KernelInterface::class)->disableOriginalConstructor()->getMock();

        // Mock expectations
        $request->expects($this->once())->method('getSession')->willReturn($session);
        $session->expects($this->once())->method('get')->with('sylius_order_id')->willReturn(88);
        $orderRepository->expects($this->once())->method('find')->with(88)->willReturn($order);
        $controller->expects($this->any())->method('thankYouAction')->willReturn(new Response());

        // Create event
        $event = new ControllerEvent($kernel, [$controller, 'thankYouAction'], $request, HttpKernelInterface::MAIN_REQUEST);

        // Service and listener
        $service = new AddTransaction($gtm, $channelContext, $currencyContext);
        $listener = new ThankYouListener($service, $orderRepository);

        // Run listener
        $listener->onKernelController($event);

        // Check result
        $this->assertArrayHasKey('ecommerce', $gtm->getData());
    }
}
