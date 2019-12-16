<?php

namespace Ignittion\Short;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Ignittion\Short\Exceptions\ShortHttpException;

/**
 * Short.cm URL shortening service API.
 * 
 * @package Short-cm
 * @author ignittion
 */
class Short
{
    /**
     * The API URL.
     *
     * @var string
     */
    protected $api;

    /**
     * The Short URL domain.
     *
     * @var string|null
     */
    protected $domain;

    /**
     * The GuzzleHttp Client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The API Key.
     *
     * @var string
     */
    protected $key;

    /**
     * Constructor
     *
     * @param string $api
     * @param string $domain
     * @param string $key
     * @return void
     */
    public function __construct(string $api, string $domain, string $key)
    {
        $this->api      = $api;
        $this->domain   = $domain;
        $this->client   = new GuzzleClient;
        $this->key      = $key;
    }

    /**
     * Make a request to the API and return the response.
     *
     * @param string $verb
     * @param string $uri
     * @param array $body
     * @param array $query
     * @param array $additionalHeaders
     * @return \stdClass|array
     */
    protected function call(string $verb = 'GET', string $uri, array $body = [], array $query = [], array $additionalHeaders = [])
    {
        $apiUrl     = rtrim($this->api, '/') . '/' . ltrim($uri, '/');
        $headers    = array_merge($additionalHeaders, [
            'Content-Type'  => 'application/json',
            'Authorization' => $this->key,
        ]);
        $request    = ['headers'    => $headers];
        $verb       = strtoupper($verb);

        // append the default domain if we aren't given a custom one
        if ($verb == 'GET') {
            if ((isset($body['domain']) && is_null($body['domain'])) || ! isset($body['domain'])) {
                $query['domain']    = $this->domain;
            }
        } else {
            if ((isset($body['domain']) && is_null($body['domain'])) || ! isset($body['domain'])) {
                $body['domain']    = $this->domain;
            }
        }

        if (count($body)) {
            $request[RequestOptions::JSON]  = $body;
        }

        if (count($query)) {
            $request['query']   = $query;
        }

        $response   = $this->client->request($verb, $apiUrl, $request);

        return json_decode($response->getBody());
    }

    /**
     * Get a list of registered domains.
     *
     * @throws \Ignittion\Short\Exceptions\ShortHttpException when an
     *      \GuzzleHttp\Exception\ClientException is thrown.
     * 
     * @see https://shortcm.docs.apiary.io/#reference/0/domain-api/list-domains
     * 
     * @return array
     */
    public function domainList() : array
    {
        try {
            return $this->call('GET', 'api/domains');
        } catch (ClientException $e) {
            throw new ShortHttpException($e->getResponse());
        }
    }

    /**
     * Create a new short url.
     *
     * @see https://shortcm.docs.apiary.io/#reference/0/link-api/create-a-new-url
     *
     * @param string $originalURL
     * @param string|null $title
     * @param string|null $path
     * @param array|null $tags
     * @param bool|null $allowDuplicates
     * @param string|null $expiresAt
     * @param string|null $expiredURL
     * @param string|null $password
     * @param string|null $utmSource
     * @param string|null $utmMedium
     * @param string|null $utmCampaign
     * @param string|null $domain
     * @return \stdClass
     * @throws ShortHttpException
     */
    public function linkCreate(string $originalURL, string $title = null, string $path = null, array $tags = null,
            bool $allowDuplicates = null, string $expiresAt = null, string $expiredURL = null, string $password = null,
            string $utmSource = null, string $utmMedium = null, string $utmCampaign = null, string $domain = null) : \stdClass
    {
        try {
            $body   = [];
            foreach (['originalURL', 'title', 'path', 'tags', 'allowDuplicates', 'expiresAt', 'expiredURL', 'password', 'utmSource',
                'utmMedium', 'utmCampaign', 'domain'] as $item) {
                if (! is_null($$item)) {
                    $body[$item]    = $$item;
                }
            }
            return $this->call('POST', 'links', $body);
        } catch (ClientException $e) {
            throw new ShortHttpException($e->getResponse());
        }
    }

    /**
     * Bulk create new short urls.
     *
     * @see https://short.cm/api#/Link%20editing/LinksBulkPost
     *
     * @param array $links
     * @return array
     * @throws ShortHttpException
     */
    public function linksBulkCreate(array $links) : array
    {
        try {
            $body = array_map([$this, 'prepareLinkBody'], $links);
            return $this->call('POST', 'links/bulk', ['links' => $body]);
        } catch (ClientException $e) {
            throw new ShortHttpException($e->getResponse());
        }
    }

    /**
     * @param array $link
     *  $link = [
     *      'originalURL'       string
     *      'title'             string|null
     *      'path'              string|null
     *      'tags'              array|null
     *      'allowDuplicates'   bool|null
     *      'expiresAt'         string|null
     *      'expiredURL'        string|null
     *      'iphoneURL'         string|null
     *      'androidURL'        string|null
     *      'password'          string|null
     *      'utmSource'         string|null
     *      'utmMedium'         string|null
     *      'utmCampaign'       string|null
     *      'utmTerm'           string|null
     *      'utmContent'        string|null
     *      'cloaking'          integer|null
     *      'redirectType'      integer|null
     *      'id'                integer|null
     *      'domainId'          integer|null
     *  ]
     * @return array
     * @throws \Exception
     */
    public function prepareLinkBody(array $link) : array
    {
        $fields = [
            'originalURL' => ['type' => 'string', 'required' => true],
            'title' => ['type' => 'string'],
            'path' => ['type' => 'string'],
            'tags' => ['type' => 'array'],
            'allowDuplicates' => ['type' => 'bool'],
            'expiresAt' => ['type' => 'string'],
            'expiredURL' => ['type' => 'string'],
            'iphoneURL' => ['type' => 'string'],
            'androidURL' => ['type' => 'string'],
            'password' => ['type' => 'string'],
            'utmSource' => ['type' => 'string'],
            'utmMedium' => ['type' => 'string'],
            'utmCampaign' => ['type' => 'string'],
            'utmTerm' => ['type' => 'string'],
            'utmContent' => ['type' => 'string'],
            'cloaking' => ['type' => 'integer'],
            'redirectType' => ['type' => 'integer'],
            'id' => ['type' => 'integer'],
            'domainId' => ['type' => 'integer'],
        ];

        $body = [];
        foreach ($fields as $fieldKey => $field) {
            $fieldValue = $link[$fieldKey] ?? null;
            $isRequired = $field['required'] ?? false;
            $fieldType = $field['type'] ?? null;

            //validate input: required fields
            if(is_null($fieldValue) && $isRequired) {
                throw new \Exception("Supplied link is not valid. $fieldKey is required.");
            }
            if(is_null($fieldValue)) {
                continue;
            }

            //validate input: field types
            if($fieldType && gettype($fieldValue) !== $fieldType) {
                throw new \Exception("Supplied link is not valid. $fieldKey type should be $fieldType.");
            }

            $body[$fieldKey] = $fieldValue;
        }

        return $body;
    }

    /**
     * Delete a short url.
     * 
     * @throws \Ignittion\Short\Exceptions\ShortHttpException when an
     *      \GuzzleHttp\Exception\ClientException is thrown.
     * 
     * @see https://shortcm.docs.apiary.io/#reference/0/link-api/delete-url
     *
     * @param integer $linkId
     * @return void
     */
    public function linkDelete(int $linkId)
    {
        try {
            return $this->call('DELETE', "links/{$linkId}");
        } catch (ClientException $e) {
            throw new ShortHttpException($e->getResponse());
        }
    }

    /**
     * Expand a short url by a given path.
     * 
     * @throws \Ignittion\Short\Exceptions\ShortHttpException when an
     *      \GuzzleHttp\Exception\ClientException is thrown.
     *
     * @see https://shortcm.docs.apiary.io/#reference/0/link-api/expand-api
     * 
     * @param string $path
     * @param string $domain
     * @return \stdClass
     */
    public function linkExpand(string $path, string $domain = null) : \stdClass
    {
        try {
            $query  = [];
            foreach (['path', 'domain'] as $item) {
                if (! is_null($$item)) {
                    $query[$item]   = $$item;
                }
            }

            return $this->call('GET', 'links/expand', [], $query);
        } catch (ClientException $e) {
            throw new ShortHttpException($e->getResponse());
        }
    }

    /**
     * Expand a short url by the original url.
     *
     * @throws \Ignittion\Short\Exceptions\ShortHttpException when an
     *      \GuzzleHttp\Exception\ClientException is thrown.
     * 
     * @see https://shortcm.docs.apiary.io/#reference/0/link-api/expand-api-by-long-url
     * 
     * @param string $originalUrl
     * @param string $domain
     * @return \stdClass
     */
    public function linkExpandByLongUrl(string $originalURL, string $domain = null) : \stdClass
    {
        try {
            $query  = [];
            foreach (['originalURL', 'domain'] as $item) {
                if (! is_null($$item)) {
                    $query[$item]   = $$item;
                }
            }

            return $this->call('GET', 'links/by-original-url', [], $query);
        } catch (ClientException $e) {
            throw new ShortHttpException($e->getResponse());
        }
    }

    /**
     * Get statistics for a short url.
     * 
     * @throws \Ignittion\Short\Exceptions\ShortHttpException when an
     *      \GuzzleHttp\Exception\ClientException is thrown.
     * 
     * @see https://shortcm.docs.apiary.io/#reference/0/link-api/analytics-api
     *
     * @param integer $linkId
     * @param string $period
     * @return \stdClass
     */
    public function linkStats(int $linkId, string $period = 'total') : \stdClass
    {
        try {
            $query  = ['period' => $period];

            return $this->call('GET', "links/statistics/{$linkId}", [], $query);
        } catch (ClientException $e) {
            throw new ShortHttpException($e->getResponse());
        }
    }

    /**
     * Update an existing short url.
     * 
     * @throws \Ignittion\Short\Exceptions\ShortHttpException when an
     *      \GuzzleHttp\Exception\ClientException is thrown.
     * 
     * @see https://shortcm.docs.apiary.io/#reference/0/link-api/update-existing-url
     *
     * @param integer $linkId
     * @param string $originalURL
     * @param string $path
     * @param string $title
     * @param string $iphoneURL
     * @param string $androidURL
     * @param string $winmobileURL
     * @return \stdClass
     */
    public function linkUpdateExisting(int $linkId, string $originalURL, string $path = null, string $title = null, 
        string $iphoneURL = null, string $androidURL = null, string $winmobileURL = null) : \stdClass
    {
        try {
            $body   = [];
            foreach (['originalURL', 'path', 'title', 'iphoneURL', 'androidURL', 'winmobileURL'] as $item) {
                if (! is_null($$item)) {
                    $body[$item]    = $$item;
                }
            }

            return $this->call('POST', "links/{$linkId}", $body);
        } catch (ClientException $e) {
            throw new ShortHttpException($e->getResponse());
        }
    }
}