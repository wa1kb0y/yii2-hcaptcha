<?php
/**
 * @link https://github.com/wa1kb0y/yii2-hcaptcha
 * @copyright Copyright (c) 2014-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace walkboy\hcaptcha;

use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\httpclient\Request as HttpRequest;
use yii\web\View;

/**
 * hCaptcha validator.
 *
 * @author HimikLab
 * @package walkboy\hcaptcha
 */
class HCaptchaValidator extends HCaptchaBaseValidator
{
    /** @var string */
    public string $uncheckedMessage = 'Cannot be blank.';

    public function __construct(
        $secret = null,
        $siteVerifyUrl = null,
        $checkHostName = null,
        ?HttpRequest $httpClientRequest = null,
        $config = []
    )
    {
        if ($secret && !$this->secret) {
            $this->secret = $secret;
        }

        parent::__construct($siteVerifyUrl, $checkHostName, $httpClientRequest, $config);
    }

    /**
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        $this->configComponentProcess();
    }

    /**
     * @param Model $model
     * @param string $attribute
     * @param View $view
     */
    public function clientValidateAttribute($model, $attribute, $view): string
    {
        $message = \addslashes($this->uncheckedMessage ?: Yii::t(
            'yii',
            '{attribute} cannot be blank.',
            ['attribute' => $model->getAttributeLabel($attribute)]
        ));

        return <<<JS
if (!value) {
     messages.push("{$message}");
}
JS;
    }

    /**
     * @param string|array $value
     * @return array|null
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function validateValue($value): ?array
    {
        if ($this->isValid === null) {
            if (!$value) {
                $this->isValid = false;
            } else {
                $response = $this->getResponse($value);
                if (!isset($response['success'], $response['hostname']) ||
                    ($this->checkHostName && $response['hostname'] !== $this->getHostName())
                ) {
                    if (isset($response['error-codes'])) {
                        return ['hCaptcha verification error: ' . $response['error-codes'][0], []];
                    }
                    throw new Exception('Invalid hCaptcha verify response.');
                }

                $this->isValid = $response['success'] === true;
            }
        }

        return $this->isValid ? null : [$this->message, []];
    }

    /**
     * @throws InvalidConfigException
     */
    protected function configComponentProcess(): void
    {
        parent::configComponentProcess();

        /** @var HCaptchaConfig $config */
        $config = Yii::$app->get($this->configComponentName, false);

        if (!$this->secret) {
            if ($config && $config->secret) {
                $this->secret = $config->secret;
                return;
            }
            throw new InvalidConfigException('hCaptcha: required `secret` param isn\'t set.');
        }
    }

    protected function getHostName(): string
    {
        return Yii::$app->request->hostName;
    }
}
