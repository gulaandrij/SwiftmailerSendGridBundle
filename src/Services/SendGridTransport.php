<?php

namespace ExpertCoder\Swiftmailer\SendGridBundle\Services;

use finfo;
use Psr\Log\LoggerInterface;
use SendGrid;
use SendGrid\Mail\Attachment;
use SendGrid\Mail\Bcc;
use SendGrid\Mail\Cc;
use SendGrid\Mail\Content;
use SendGrid\Mail\From;
use SendGrid\Mail\Mail;
use SendGrid\Mail\MailSettings;
use SendGrid\Mail\To;
use Swift_Events_EventListener;
use Swift_Transport;

/**
 * Class SendGridTransport
 *
 * @package ExpertCoder\Swiftmailer\SendGridBundle\Services
 */
class SendGridTransport implements Swift_Transport
{
    /**
     *
     * @see https://sendgrid.com/docs/API_Reference/Web_API_v3/Mail/errors.html
     * 2xx responses indicate a successful request. The request that you made is valid and successful.
     */
    public const STATUS_SUCCESSFUL_MAX_RANGE = 299;

    /**
     *
     * @see https://sendgrid.com/docs/API_Reference/Web_API_v3/Mail/errors.html
     * ACCEPTED : Your message is both valid, and queued to be delivered.
     */
    public const STATUS_ACCEPTED = 202;

    /**
     *
     * @see https://sendgrid.com/docs/API_Reference/Web_API_v3/Mail/errors.html
     * OK : Your message is valid, but it is not queued to be delivered. Sandbox mode only.
     */
    public const STATUS_OK_SUCCESSFUL_MIN_RANGE = 200;

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

    /**
     *
     * @var bool
     */
    private $sandMode;

    /**
     * SendGridTransport constructor.
     *
     * @param string $sendGridApiKey
     * @param array  $sendGridCategories
     * @param bool   $sandMode
     */
    public function __construct(string $sendGridApiKey, array $sendGridCategories, bool $sandMode)
    {
        $this->sendGridApiKey = $sendGridApiKey;
        $this->sendGridCategories = $sendGridCategories;
        $this->sandMode = $sandMode;
    }

    /**
     *
     * @return bool
     */
    public function isStarted(): bool
    {
        //Not used
        return true;
    }

    /**
     * Start
     */
    public function start()
    {
        //Not used
    }

    /**
     * Stop
     */
    public function stop()
    {
        //Not used
    }

    /**
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
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
    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null): int
    {
        // prepare fake data.
        $sent = 0;
        $prepareFailedRecipients = [];

        $Email = new Mail();

        $emailSettings = new MailSettings();
        $emailSettings->setSandboxMode($this->sandMode);

        $Email->setMailSettings($emailSettings);

        foreach ($message->getFrom() as $email => $name) {
            $Email->setFrom(new From($email, $name));
            break;
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

        if ($toArr = $message->getTo()) {
            foreach ($toArr as $email => $name) {
                $Email->addTo(new To($email, $name));
                ++$sent;
                $prepareFailedRecipients[] = $email;
            }
        }
        if ($toArr = $message->getCc()) {
            foreach ($toArr as $email => $name) {
                $Email->addCc(new Cc($email, $name));
                ++$sent;
                $prepareFailedRecipients[] = $email;
            }
        }
        if ($toArr = $message->getBcc()) {
            foreach ($toArr as $email => $name) {
                $Email->addBcc(new Bcc($email, $name));
                ++$sent;
                $prepareFailedRecipients[] = $email;
            }
        }

        // process attachment
        if ($attachments = $message->getChildren()) {
            foreach ($attachments as $attachment) {
                if ($attachment instanceof \Swift_Mime_Attachment) {
                    $sAttachment = new Attachment();
                    $sAttachment->setContent(base64_encode($attachment->getBody()));
                    $sAttachment->setType($attachment->getContentType());
                    $sAttachment->setFilename($attachment->getFilename());
                    $sAttachment->setDisposition($attachment->getDisposition());
                    $sAttachment->setContentID($attachment->getId());
                    $Email->addAttachment($sAttachment);
                } elseif (\in_array($attachment->getContentType(), ['text/plain', 'text/html'])) {
                    // add part if any is defined, to avoid error please set body as text and part as html
                    $Email->addContent(new Content($attachment->getContentType(), $attachment->getBody()));
                }
            }
        }

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

    /**
     *
     * @param Swift_Events_EventListener $plugin
     */
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
