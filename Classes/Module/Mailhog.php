<?php

namespace Surplex\Codeception\Mailhog\Module;

/*
 * This file is part of the Surplex\Codeception-Mailhog package.
 *
 * This package is open source software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Surplex\Codeception\Mailhog\Domain\MailHogClient;
use Surplex\Codeception\Mailhog\Domain\Model\Mail;


class Mailhog extends Module
{

    protected MailHogClient $mailHogClient;

    protected ?Mail $currentMail = null;

    /**
     * Mailhog constructor.
     * @param ModuleContainer $moduleContainer
     * @param mixed[]|null $config
     */
    public function __construct(ModuleContainer $moduleContainer, array $config = null)
    {
        parent::__construct($moduleContainer, $config);
        $this->mailHogClient = new MailHogClient($config['base_uri'] ?? null);
    }

    /**
     * @param int $numberOfMails
     * @throws Exception
     */
    public function inboxContainsNumberOfMails(int $numberOfMails): void
    {
        $this->assertEquals($numberOfMails, $this->mailHogClient->countAll());
    }


    /**
     * @param int $numberOfMails
     * @param int $timeoutInSeconds
     * @throws Exception
     */
    public function waitUntilInboxContainsNumberOfMails(int $numberOfMails, int $timeoutInSeconds = 10): void
    {
        for ($i = 0; $i < $timeoutInSeconds; $i++) {
            $mailCount = $this->mailHogClient->countAll();
            if ($mailCount === $numberOfMails) {
                $this->assertEquals($numberOfMails, $mailCount);
                return;
            }
            codecept_debug('Waiting 1 second for MailHog to catch up');
            sleep(1);
        }
        throw new Exception('Expected number of mails not present in MailHog after 10 seconds');
    }

    /**
     * @throws GuzzleException
     */
    public function clearInbox(): void
    {
        $this->mailHogClient->deleteAllMessages();
    }

    /**
     * @param int $mailNumber
     * @throws GuzzleException
     */
    public function openMailByNumber(int $mailNumber): void
    {
        $mailIndex = $mailNumber - 1;
        $this->currentMail = $this->mailHogClient->findOneByIndex($mailIndex);

        $this->assertInstanceOf(Mail::class, $this->currentMail, 'The mail with number ' . $mailNumber . ' does not exist.');
    }

    /**
     * @param string $text
     * @throws Exception
     */
    public function seeTextInMail(string $text): void
    {
        $mail = $this->parseMailBody($this->currentMail->getBody());
        if (stristr($mail, $text)) {
            return;
        }
        throw new Exception(sprintf('Did not find the text "%s" in the mail', $text));
    }

    public function getCurrentMail(): Mail
    {
        return $this->currentMail;
    }

    /**
     * @param string $address
     * @throws Exception
     */
    public function checkRecipientAddress(string $address): void
    {
        $recipients = $this->currentMail->getRecipients();
        foreach ($recipients as $recipient) {
            if ($recipient === $address) {
                return;
            }
        }
        throw new Exception(sprintf('Did not find the recipient "%s" in the mail', $address));
    }

    /**
     * @throws Exception
     */
    public function checkIfSpam(): void
    {
        $subjectArray = $this->currentMail->getSubject();

        foreach ($subjectArray as $subject) {
            if (strpos($subject, "[SPAM]") === 0) {
                return;
            }
        }

        throw new Exception(sprintf('Could not find [SPAM] at the beginning of subject "%s"', $subject));
    }

    /**
     * @param string $mailBody
     * @return string
     */
    protected function parseMailBody(string $mailBody): string
    {
        $unescapedMail = preg_replace('/(=(\r\n|\n|\r))|(?=)3D/', '', $mailBody);
        if (preg_match('/(.*)Content-Type\: text\/html/s', $unescapedMail)) {
            $unescapedMail = strip_tags($unescapedMail, '<a><img>');
        }
        return $unescapedMail;
    }

}
