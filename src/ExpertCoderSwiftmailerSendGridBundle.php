<?php

namespace ExpertCoder\Swiftmailer\SendGridBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ExpertCoderSwiftmailerSendGridBundle
 * @package ExpertCoder\Swiftmailer\SendGridBundle
 */
class ExpertCoderSwiftmailerSendGridBundle extends Bundle
{
    /**
     * Returns the bundle name that this bundle overrides.
     *
     * Despite its name, this method does not imply any parent/child relationship
     * between the bundles, just a way to extend and override an existing
     * bundle.
     *
     * @return string The Bundle name it overrides or null if no parent
     */
    public function getParent()
    {
        return null;
    }
}
