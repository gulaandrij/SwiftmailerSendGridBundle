<?php

namespace ExpertCoder\Swiftmailer\SendGridBundle\Tests;

use ExpertCoder\Swiftmailer\SendGridBundle\ExpertCoderSwiftmailerSendGridBundle;
use ExpertCoder\Swiftmailer\SendGridBundle\Services\SendGridTransport;
use Nyholm\BundleTest\BaseBundleTestCase;
use Nyholm\BundleTest\CompilerPass\PublicServicePass;

/**
 * Class BundleInitializationTest
 *
 * @package ExpertCoder\Swiftmailer\SendGridBundle\Tests
 */
class BundleInitializationTest extends BaseBundleTestCase
{

    /**
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     *
     * @return string
     */
    protected function getBundleClass(): string
    {
        return ExpertCoderSwiftmailerSendGridBundle::class;
    }

    protected function setUp()
    {
        parent::setUp();

        // Make services public that have an idea that matches a regex
        $this->addCompilerPass(new PublicServicePass('|expertcoder_swift_mailer.*|'));

        // Create a new Kernel
        $kernel = $this->createKernel();

        // Add some configuration
        $kernel->addConfigFile(__DIR__.'/config_test.yml');

        // Boot the kernel.
        $this->bootKernel();

        // Get the container
        $this->container = $this->getContainer();
    }

    public function testInitBundle()
    {
        // Test if services exists
        $this->assertTrue($this->container->has('expertcoder_swift_mailer.send_grid.transport'));
        $service = $this->container->get('expertcoder_swift_mailer.send_grid.transport');
        $this->assertInstanceOf(SendGridTransport::class, $service);

        // Test if parameters exists
        $this->assertTrue($this->container->hasParameter('expertcoder_swiftmailer_sendgrid.api_key'));
        $this->assertTrue($this->container->hasParameter('expertcoder_swiftmailer_sendgrid.categories'));
        $this->assertTrue($this->container->hasParameter('expertcoder_swiftmailer_sendgrid.sandbox_mode'));
    }

    public function testSend()
    {
        $message = (new \Swift_Message())
            // Give the message a subject
            ->setSubject('Your subject')
            // Set the From address with an associative array
            ->setFrom(
                [
                 'john@doe.com'  => 'John Doe',
                 'john1@doe.com' => 'John Doe',
                 'john2@doe.com' => 'John Doe',
                 'john3@doe.com' => 'John Doe',
                ]
            )
            // Set the To addresses with an associative array (setTo/setCc/setBcc)
            ->setTo('gyla.andrij@gmail.com')
            ->setCc('gyla1.andrij@gmail.com')
            ->setBcc(
                ['gyla2.andrij@gmail.com']
            )
            // Give it a body
            ->setBody('Here is the message itself')
            // And optionally an alternative body
            ->addPart('<q>Here is the message itself</q>', 'text/html')
            // Optionally add any attachments
            ->attach(\Swift_Attachment::fromPath('./tests/test.jpg'));

        $sm = new \Swift_Mailer($this->container->get('expertcoder_swift_mailer.send_grid.transport'));
        $sended = $sm->send($message);

        $this->assertEquals(3, $sended);
    }
}
