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
    protected function getBundleClass()
    {
        return ExpertCoderSwiftmailerSendGridBundle::class;
    }

    protected function setUp()
    {
        parent::setUp();

        // Make services public that have an idea that matches a regex
        $this->addCompilerPass(new PublicServicePass('|expertcoder_swift_mailer.*|'));
    }

    public function testInitBundle()
    {
        // Create a new Kernel
        $kernel = $this->createKernel();

        // Add some configuration
        $kernel->addConfigFile(__DIR__.'/config_test.yml');

        // Boot the kernel.
        $this->bootKernel();

        // Get the container
        $container = $this->getContainer();

        // Test if services exists
        $this->assertTrue($container->has('expertcoder_swift_mailer.send_grid.transport'));
        $service = $container->get('expertcoder_swift_mailer.send_grid.transport');
        $this->assertInstanceOf(SendGridTransport::class, $service);

        // Test if parameters exists
        $this->assertTrue($container->hasParameter('expertcoder_swiftmailer_sendgrid.api_key'));
        $this->assertTrue($container->hasParameter('expertcoder_swiftmailer_sendgrid.categories'));
        $this->assertTrue($container->hasParameter('expertcoder_swiftmailer_sendgrid.sandbox_mode'));
    }

    public function testSend()
    {

        $message = (new \Swift_Message())
            // Give the message a subject
            ->setSubject('Your subject')
            // Set the From address with an associative array
            ->setFrom(['john@doe.com' => 'John Doe', 'john1@doe.com' => 'John Doe', 'john2@doe.com' => 'John Doe', 'john3@doe.com' => 'John Doe'])
            // Set the To addresses with an associative array (setTo/setCc/setBcc)
            ->setTo('gyla.andrij@gmail.com')
            ->setCc('gyla1.andrij@gmail.com')
            ->setBcc('gyla2.andrij@gmail.com')
            // Give it a body
            ->setBody('Here is the message itself')
            // And optionally an alternative body
            ->addPart('<q>Here is the message itself</q>', 'text/html')
            // Optionally add any attachments
            ->attach(\Swift_Attachment::fromPath('my-document.pdf'));

        // Create a new Kernel
        $kernel = $this->createKernel();

        // Add some configuration
        $kernel->addConfigFile(__DIR__.'/config_test.yml');

        // Boot the kernel.
        $this->bootKernel();

        $container = $this->getContainer();
        $sm = new \Swift_Mailer($container->get('expertcoder_swift_mailer.send_grid.transport'));
        $sm->send($message);

        dd($sm);
    }
}
