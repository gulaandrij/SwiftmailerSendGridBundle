<?php

namespace ExpertCoder\Swiftmailer\SendGridBundle\Services;

use SendGrid\Mail\Mail;

/**
 * Class ProcessMessages
 *
 * @package ExpertCoder\Swiftmailer\SendGridBundle\Services
 */
class ProcessMessages
{
    /**
     *
     * @param Mail                      $mail
     * @param \Swift_Mime_SimpleMessage $message
     * @param string                    $type
     *
     * @return int
     */
    public function process(&$mail, \Swift_Mime_SimpleMessage $message, string $type): int
    {
        $sent = 0;
        if ($toArr = $message->getTo()) {
            foreach ($toArr as $email => $name) {
                $mail->addTo(new $type($email, $name));
                ++$sent;
                $prepareFailedRecipients[] = $email;
            }
        }

        return $sent;
    }
}
