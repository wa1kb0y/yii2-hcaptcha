<?php
/**
 * @link https://github.com/wa1kb0y/yii2-hcaptcha
 * @copyright Copyright (c) 2014-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace walkboy\hcaptcha;

use Yii;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\httpclient\Client as HttpClient;
use yii\httpclient\Request as HttpRequest;
use yii\validators\Validator;

/**
 * hCaptcha widget validator base class.
 *
 * @author HimikLab
 * @package walkboy\hcaptcha
 */
abstract class HCaptchaBaseValidator extends Validator
{
    /** The shared key between your site and hCaptcha. */
    public string $secret = '';

    /**
     * Default is HCaptchaConfig::SITE_VERIFY_URL_DEFAULT.
     */
    public string $siteVerifyUrl = '';

    public ?HttpRequest $httpClientRequest = null;

    public string $configComponentName = 'hCaptcha';

    /** Check host name. Default is false. */
    public ?bool $checkHostName = null;

    protected ?bool $isValid = null;

    public function __construct(
        $siteVerifyUrl,
        $checkHostName,
        $httpClientRequest,
        $config
    )
    {
        if ($siteVerifyUrl && !$this->siteVerifyUrl) {
            $this->siteVerifyUrl = $siteVerifyUrl;
        }
        if ($checkHostName && $this->checkHostName !== null) {
            $this->checkHostName = $checkHostName;
        }
        if ($httpClientRequest && !$this->httpClientRequest) {
            $this->httpClientRequest = $httpClientRequest;
        }

        parent::__construct($config);
    }

    public function init()
    {
        parent::init();

        if ($this->message === null) {
            $this->message = Yii::t('yii', 'The verification code is incorrect.');
        }
    }

    /**
     * @throws Exception|InvalidArgumentException
     */
    protected function getResponse(string $value): array
    {
        $response = $this->httpClientRequest
            ->setMethod('GET')
            ->setUrl($this->siteVerifyUrl)
            ->setData(['secret' => $this->secret, 'response' => $value, 'remoteip' => Yii::$app->request->userIP])
            ->send();
        if (!$response->isOk) {
            throw new Exception('Unable connection to the captcha server. Status code ' . $response->statusCode);
        }

        return $response->data;
    }

    protected function configComponentProcess(): void
    {
        /** @var HCaptchaConfig $config */
        $config = Yii::$app->get($this->configComponentName, false);

        if (!$this->siteVerifyUrl) {
            $this->siteVerifyUrl = HCaptchaConfig::SITE_VERIFY_URL_DEFAULT;
            if ($config && $config->siteVerifyUrl) {
                $this->siteVerifyUrl = $config->siteVerifyUrl;
            }
        }

        if ($this->checkHostName === null) {
            $this->checkHostName = false;
            if ($config) {
                $this->checkHostName = $config->checkHostName;
            }
        }

        if (!$this->httpClientRequest) {
            if ($config && $config->httpClientRequest) {
                $this->httpClientRequest = $config->httpClientRequest;
            } else {
                $this->httpClientRequest = (new HttpClient())->createRequest();
            }
        }

        if ($this->httpClientRequest->client === null) {
            $this->httpClientRequest->client = new HttpClient();
        }
    }
}
