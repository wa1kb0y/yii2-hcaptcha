<?php
/**
 * @link https://github.com/wa1kb0y/yii2-hcaptcha
 * @copyright Copyright (c) 2014-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace walkboy\hcaptcha;

use \yii\httpclient\Request;

/**
 * hCaptcha global config.
 * 
 * @author HimikLab
 * @package walkboy\hcaptcha
 */
class HCaptchaConfig
{
    const JS_API_URL_DEFAULT = 'https://js.hcaptcha.com/1/api.js';

    const SITE_VERIFY_URL_DEFAULT = 'https://api.hcaptcha.com/siteverify';

    /** Your site key for hCaptcha */
    public string $siteKey;

    /** Your secret for hCaptcha */
    public string $secret;

    /** JS widget URL */
    public string $jsApiUrl = self::JS_API_URL_DEFAULT;

    /** Verification URL */
    public string $siteVerifyUrl = self::SITE_VERIFY_URL_DEFAULT;

    /** Check host name. */
    public bool $checkHostName = false;

    /** Request instance */
    public ?Request $httpClientRequest = null;
}
