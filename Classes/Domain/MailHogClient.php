<?php

namespace Surplex\Codeception\Mailhog\Domain;

/*
 * This file is part of the Surplex\Codeception-Mailhog package.
 *
 * This package is open source software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Surplex\Codeception\Mailhog\Domain\Model\Mail;

class MailHogClient
{

    protected Client $client;

    /**
     * MailHogClient constructor.
     * @param string $baseUri
     */
    public function __construct(string $baseUri = 'http://127.0.0.1:8025')
    {
        $this->client = new Client([
            'base_uri' => $baseUri,
            'cookies' => true,
            'headers' => [
                'User-Agent' => 'FancySurplexGuzzleTestingAgent'
            ]
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function deleteAllMessages(): void
    {
        $this->client->delete('/api/v1/messages');
    }

    /**
     * @return int
     * @throws Exception
     */
    public function countAll(): int
    {
        $data = $this->getDataFromMailHog('api/v2/messages?start=0&limit=1');
        return (int) $data['total'];
    }

    /**
     * @param $index
     * @return Mail
     * @throws GuzzleException
     */
    public function findOneByIndex(int $index): Mail
    {
        $apiCall = sprintf('api/v2/messages', $index);
        $result = $this->client->get($apiCall)->getBody();

        if (($data = json_decode($result, true)) !== false) {
            $currentMailData = $data['items'][$index];
            return $this->buildMailObjectFromJson($currentMailData);
        }
    }

    /**
     * @param $apiCall
     * @return array
     * @throws Exception|GuzzleException
     */
    protected function getDataFromMailHog($apiCall): array
    {
        $result = $this->client->get($apiCall)->getBody();

        $data = json_decode($result, true);

        if ($data === false) {
            throw new Exception('The mailhog result could not be parsed to json', 1467038556);
        }

        return $data;
    }

    /**
     * @param array $data
     * @return Mail
     */
    protected function buildMailObjectFromJson(array $data): Mail
    {
        return new Mail($data);
    }
}
