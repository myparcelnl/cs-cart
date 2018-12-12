<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Shippings\Services\Myparcel\Webhooks;

use Tygh\Http;
use Tygh\Shippings\Services\Myparcel\Traits\ExtraHeaderTrait;

/**
 * Class Subscription
 * Webhooks subscriptions management
 * @package Tygh\Shippings\Services\Myparcel\Webhooks
 */
class Subscription
{
    use ExtraHeaderTrait;

    const SUBSCRIPTIONS_URL = 'https://api.myparcel.nl/webhook_subscriptions';

    /**
     * id
     * Data type: integer
     * Required: No.
     * The id of the webhook subscription.
     */
    private $id = 0;
    /**
     * hook
     * Data type: string
     * Required: Yes.
     * The event from which you want to receive notifications.
     */
    private $hook = '';
    /**
     * url
     * Data type: string
     * Required: Yes.
     *The callback URL on which to receive notifications. The URL must be https.
     */
    private $callback_url = '';
    /**
     * account_id
     * Data type: integer
     * Required: No.
     * The account id to which this subscription belongs.
     */
    private $account_id = 0;
    /**
     * shop_id
     * Data type: integer
     * Required: No.
     * The shop id to which this subscription belongs.
     */
    private $shop_id = 0;

    /**
     * @var string
     */
    private $api_key = '';

    private $token = '';

    private $response = '';

    private $shipping_id = 0;

    /**
     * Subscription constructor.
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        if (!empty($params['id'])) {
            $this->id = intval($params['id']);
        }

        if (!empty($params['hook'])) {
            $this->hook = strval($params['hook']);
        }

        if (!empty($params['url'])) {
            $this->callback_url = strval($params['url']);
        }

        if (!empty($params['account_id'])) {
            $this->account_id = intval($params['account_id']);
        }

        if (!empty($params['shop_id'])) {
            $this->shop_id = intval($params['shop_id']);
        }

        if (!empty($params['shipping_id'])) {
            $this->shipping_id = intval($params['shipping_id']);
        }

        if (!empty($params['api_key'])) {
            $this->api_key = strval($params['api_key']);
        }

        if (!empty($params['token'])) {
            $this->token = strval($params['token']);
        }
    }

    /**
     * @return mixed
     */
    public function getCallbackUrl()
    {
        return $this->callback_url;
    }

    /**
     * @return $this
     */
    public function add()
    {
        $url = self::SUBSCRIPTIONS_URL;
        $data = [
            'data' => [
                'webhook_subscriptions' => [
                    [
                        'hook' => $this->hook,
                        'url' => $this->callback_url,
                    ],
                ],
            ]
        ];
        $encoded_data = json_encode($data);
        $extra = $this->getExtraHeaders();
        $raw_response = Http::post($url, $encoded_data, $extra);
        $this->response = json_decode($raw_response, true);
        $this->setId(
            isset($this->response['data']['ids']) ?
            reset($this->response['data']['ids'])['id'] :
            0
        );

        return $this;
    }

    /**
     * @param mixed $id
     * @return Subscription
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Subscription
     */
    public function get()
    {
        $url = self::SUBSCRIPTIONS_URL . '/' . $this->id;
        $raw_response = Http::get($url, [], $this->getExtraHeaders());
        $this->response = json_decode($raw_response, true);

        if (isset($this->response['data'], $this->response['data']['webhook_subscriptions'], $this->response['data']['webhook_subscriptions'][0])) {
            $subscription = $this->response['data']['webhook_subscriptions'][0];
            $result = new Subscription($subscription);
        } else {
            $result = new Subscription([]);
        };

        return $result;
    }

    /**
     * @param int $webhook_id
     * @return array
     */
    public static function getFromDb($webhook_id)
    {
        $result = [];
        if (!empty($webhook_id)) {
            $result = db_get_row('SELECT * FROM ?:myparcel_webhooks_subscriptions WHERE id = ?i', $webhook_id);
        }

        return $result;
    }

    /**
     * @return $this
     */
    public function delete()
    {
        $url = self::SUBSCRIPTIONS_URL . '/' . $this->id;
        $raw_result = Http::delete($url, $this->getExtraHeaders());
        $result = json_decode($raw_result, true);
        if (empty($result['errors'])) {
            db_query(
                'DELETE FROM ?:myparcel_webhooks_subscriptions WHERE id = ?i',
                $this->id
            );
        }
        $this->response = $result;

        return $this;
    }

    /**
     * @return $this
     */
    public function save()
    {
        $data = [
            'id' => $this->id,
            'hook' => $this->hook,
            'url' => $this->callback_url,
            'token' => $this->token,
            'account_id' => $this->account_id,
            'shop_id' => $this->shop_id,
            'shipping_id' => $this->shipping_id,
        ];
        db_replace_into('myparcel_webhooks_subscriptions', $data);

        return $this;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return string
     */
    private function getApiKey()
    {
        return $this->api_key;
    }
}
