<?php

namespace ExpertCoder\Swiftmailer\SendGridBundle\Services;

use finfo;
use Psr\Log\LoggerInterface;
use SendGrid;
use SendGrid\Mail\Content;
use SendGrid\Mail\Mail;
use Swift_Events_EventListener;
use Swift_Transport;

class SendGridTransport implements Swift_Transport
{
    /**
     *
     * @see https://sendgrid.com/docs/API_Reference/Web_API_v3/Mail/errors.html
     * 2xx responses indicate a successful request. The request that you made is valid and successful.
     */
    const STATUS_SUCCESSFUL_MAX_RANGE = 299;

    /**
     *
     * @see https://sendgrid.com/docs/API_Reference/Web_API_v3/Mail/errors.html
     * ACCEPTED : Your message is both valid, and queued to be delivered.
     */
    const STATUS_ACCEPTED = 202;

    /**
     *
     * @see https://sendgrid.com/docs/API_Reference/Web_API_v3/Mail/errors.html
     * OK : Your message is valid, but it is not queued to be delivered. Sandbox mode only.
     */
    const STATUS_OK_SUCCESSFUL_MIN_RANGE = 200;

    /**
     * Sendgrid api key.
     *
     * @var string
     */
    private $sendGridApiKey;

    /**
     * Sendgrid mails categories.
     *
     * @var array
     */
    private $sendGridCategories;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    private $logger;

    public function __construct($sendGridApiKey, $sendGridCategories)
    {
        $this->sendGridApiKey = $sendGridApiKey;
        $this->sendGridCategories = $sendGridCategories;
    }

    public function isStarted()
    {
        //Not used
        return true;
    }

    public function start()
    {
        //Not used
    }

    public function stop()
    {
        //Not used
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * WARNING : $failedRecipients and return value are faked.
     *
     * @param \Swift_Mime_SimpleMessage $message
     * @param array                     $failedRecipients
     *
     * @return int
     */
    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        // prepare fake data.
        $sent = 0;
        $prepareFailedRecipients = [];

        $Email = new Mail();

        foreach ($message->getFrom() as $email => $name) {
            $Email->setFrom($email, $name);
            break;
        }

        foreach ($message->getTo() as $email => $name) {
            $Email->addTo($email, $name);
        }

        $Email->setSubject($message->getSubject());

        // extract content type from body to prevent multi-part content-type error
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $contentType = $finfo->buffer($message->getBody());
        $content = new Content($contentType, $message->getBody());

        $Email->addContent($content);

        // categories can be useful if you use them like tags to, for example, distinguish different applications.
        foreach ($this->sendGridCategories as $category) {
            $Email->addCategory($category);
        }

//        $personalization = new SendGrid\Personalization();
//
//        // process TO
//        if ($toArr = $message->getTo()) {
//            foreach ($toArr as $email => $name) {
//                $personalization->addTo(new SendGrid\Email($name, $email));
//                ++$sent;
//                $prepareFailedRecipients[] = $email;
//            }
//        }
//
//        // process CC
//        if ($ccArr = $message->getCc()) {
//            foreach ($ccArr as $email => $name) {
//                $personalization->addCc(new SendGrid\Email($name, $email));
//                ++$sent;
//                $prepareFailedRecipients[] = $email;
//            }
//        }
//
//        // process BCC
//        if ($bccArr = $message->getBcc()) {
//            foreach ($bccArr as $email => $name) {
//                $personalization->addBcc(new SendGrid\Email($name, $email));
//                ++$sent;
//                $prepareFailedRecipients[] = $email;
//            }
//        }
//
//        // process attachment
//        if ($attachments = $message->getChildren()) {
//            foreach ($attachments as $attachment) {
//                if ($attachment instanceof Swift_Mime_Attachment) {
//                    $sAttachment = new SendGrid\Attachment();
//                    $sAttachment->setContent(base64_encode($attachment->getBody()));
//                    $sAttachment->setType($attachment->getContentType());
//                    $sAttachment->setFilename($attachment->getFilename());
//                    $sAttachment->setDisposition($attachment->getDisposition());
//                    $sAttachment->setContentId($attachment->getId());
//                    $mail->addAttachment($sAttachment);
//                } elseif (in_array($attachment->getContentType(), ['text/plain', 'text/html'])) {
//                    // add part if any is defined, to avoid error please set body as text and part as html
//                    $mail->addContent(new SendGrid\Content($attachment->getContentType(), $attachment->getBody()));
//                }
//            }
//        }
//
//        $mail->addPersonalization($personalization);

        $sendGrid = new SendGrid($this->sendGridApiKey);

        $response = $sendGrid->send($Email);
        $responseCode = $response->statusCode();

        // only 2xx status are ok
        if ($responseCode < self::STATUS_OK_SUCCESSFUL_MIN_RANGE
            || self::STATUS_SUCCESSFUL_MAX_RANGE < $responseCode
        ) {
            // to force big boom error uncomment this line
            //throw new \Swift_TransportException("Error when sending message. Return status :".$response->statusCode());
            if (null !== $this->logger) {
                $this->logger->error($responseCode.': '.$response->body());
            }

            // copy failed recipients
            foreach ($prepareFailedRecipients as $recipient) {
                $failedRecipients[] = $recipient;
            }
            $sent = 0;
        }

        return $sent;
    }

    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        // unused
    }

    /**
     * Check if this Transport mechanism is alive.
     *
     * If a Transport mechanism session is no longer functional, the method
     * returns FALSE. It is the responsibility of the developer to handle this
     * case and restart the Transport mechanism manually.
     *
     * @example
     *
     *   if (!$transport->ping()) {
     *      $transport->stop();
     *      $transport->start();
     *   }
     *
     * The Transport mechanism will be started, if it is not already.
     *
     * It is undefined if the Transport mechanism attempts to restart as long as
     * the return value reflects whether the mechanism is now functional.
     *
     * @return bool TRUE if the transport is alive
     */
    public function ping()
    {
        return true;
    }
}
