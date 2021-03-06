<?php
namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\RunTimeException as RunTimeException;

class Validator
{
    /**
     * google client
     *
     * @var Google_Client
     */
    protected $_client = null;

    /**
     * @var \Google_Service_AndroidPublisher
     */
    protected $_androidPublisherService = null;

    /**
     * @var string
     */
    protected $_package_name = null;

    /**
     * @var string
     */
    protected $_purchase_token = null;

    /**
     * @var string
     */
    protected $_product_id = null;

    public function __construct(array $options = [])
    {
        $this->_client = new \Google_Client();
        $this->_client->setClientId($options['client_id']);
        $this->_client->setClientSecret($options['client_secret']);

        touch(sys_get_temp_dir() . '/googleplay_access_token.txt');
        chmod(sys_get_temp_dir() . '/googleplay_access_token.txt', 0777);

        try {
            $this->_client->setAccessToken(file_get_contents(sys_get_temp_dir() . '/googleplay_access_token.txt'));
        } catch (\Exception $e) {
            // skip exceptions when the access token is not valid
        }

        try {
            if ($this->_client->isAccessTokenExpired()) {
                $this->_client->refreshToken($options['refresh_token']);
                file_put_contents(sys_get_temp_dir() . '/googleplay_access_token.txt', $this->_client->getAccessToken());
            }
        } catch (\Exception $e) {
            throw new RuntimeException('Failed refreshing access token - ' . $e->getMessage());
        }

        $this->_androidPublisherService = new \Google_Service_AndroidPublisher($this->_client);

    }


    /**
     *
     * @param string $package_name
     * @return \ReceiptValidator\GooglePlay\Validator
     */
    public function setPackageName($package_name)
    {
        $this->_package_name = $package_name;

        return $this;
    }

    /**
     *
     * @param string $purchase_token
     * @return \ReceiptValidator\GooglePlay\Validator
     */
    public function setPurchaseToken($purchase_token)
    {
        $this->_purchase_token = $purchase_token;

        return $this;
    }

    /**
     *
     * @param string $product_id
     * @return \ReceiptValidator\GooglePlay\Validator
     */
    public function setProductId($product_id)
    {
        $this->_product_id = $product_id;

        return $this;
    }


    public function validate()
    {
        $response = $this->_androidPublisherService->inapppurchases->get($this->_package_name, $this->_product_id, $this->_purchase_token);

        return $response;
    }
}
