<?php
/**
 * @link https://github.com/wa1kb0y/yii2-hcaptcha
 * @copyright Copyright (c) 2014-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace walkboy\hcaptcha;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\widgets\InputWidget;

/**
 * Yii2 hCaptcha widget.
 *
 * For example:
 *
 * ```php
 * <?= $form->field($model, 'verifyKey')->widget(
 *  HCaptcha::class,
 *  [
 *   'siteKey' => 'your siteKey' // unnecessary is hCaptcha component was set up
 *  ]
 * ) ?>
 * ```
 *
 * or
 *
 * ```php
 * <?= HCaptcha::widget([
 *  'name' => 'verifyKey',
 *  'siteKey' => 'your siteKey', // unnecessary is hCaptcha component was set up
 *  'widgetOptions' => ['class' => 'col-sm-offset-3']
 * ]) ?>
 * ```
 *
 * @see https://docs.hcaptcha.com/
 * @author HimikLab
 * @author Vitaly Walkboy
 * @package walkboy\hcaptcha
 */
class HCaptcha extends InputWidget
{
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';

    const TYPE_IMAGE = 'image';
    const TYPE_AUDIO = 'audio';

    const SIZE_NORMAL = 'normal';
    const SIZE_COMPACT = 'compact';

    /**
     * Your site key.
     */
    public ?string $siteKey = null;

    /**
     * Captcha verification url. [[ReCaptchaConfig::JS_API_URL_DEFAULT]] (default)
     */
    public ?string $jsApiUrl = null;

    /**
     * The color theme of the widget. [[THEME_LIGHT]] (default) or [[THEME_DARK]]
     */
    public ?string $theme = self::THEME_LIGHT;

    /**
     * The type of CAPTCHA to serve. [[TYPE_IMAGE]] (default) or [[TYPE_AUDIO]]
     */
    public ?string $type = self::TYPE_IMAGE;

    /**
     * The size of the widget. [[SIZE_NORMAL]] (default) or [[SIZE_COMPACT]]
     */
    public ?string $size = self::SIZE_NORMAL;

    /**
     * The tabindex of the widget
     */
    public ?int $tabIndex = null;

    /**
     * Your JS callback function that's executed when the user submits a successful hCaptcha response.
     */
    public ?string $jsCallback = null;

    /**
     * Your JS callback function that's executed when the hCaptcha response expires and the user
     * needs to solve a new CAPTCHA.
     */
    public ?string $jsExpiredCallback = null;

    /**
     * Your JS callback function that's executed when hCaptcha encounters an error (usually network
     * connectivity) and cannot continue until connectivity is restored. If you specify a function here, you are
     * responsible for informing the user that they should retry.
     */
    public ?string $jsErrorCallback = null;

    /**
     * Default component name
     */
    public string $configComponentName = 'hCaptcha';

    /**
     * Additional html widget options, such as `class`.
     */
    public array $widgetOptions = [];

    public function __construct($siteKey = null, $jsApiUrl = null, $config = [])
    {
        if ($siteKey && !$this->siteKey) {
            $this->siteKey = $siteKey;
        }
        if ($jsApiUrl && !$this->jsApiUrl) {
            $this->jsApiUrl = $jsApiUrl;
        }

        parent::__construct($config);
    }

    /**
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        $this->configComponentProcess();
    }

    public function run(): void
    {
        parent::run();
        $view = $this->view;
        $arguments = \http_build_query([
            'hl' => $this->getLanguageSuffix(),
            'render' => 'explicit',
            'onload' => 'captchaOnloadCallback',
        ]);

        $view->registerJsFile(
            $this->jsApiUrl . '?' . $arguments,
            ['position' => $view::POS_END, 'async' => true, 'defer' => true]
        );
        $view->registerJs(
            <<<'JS'
            function captchaOnloadCallback() {
                "use strict";
            
                document.querySelectorAll(".h-captcha").forEach(container => {
                    if (container.dataset.hcaptchaClientId === undefined) {
                        const widgetId = hcaptcha.render(container.id, {
                            callback: function (response) {
                                const formId = container.dataset.formId || "";
                                const inputId = container.dataset.inputId;
            
                                const input = formId
                                    ? document.querySelector(`#${formId} #${inputId}`)
                                    : document.querySelector(`#${inputId}`);
            
                                if (input) {
                                    input.value = response;
                                    
                                    // Dispatch a "change" event
                                    input.dispatchEvent(new Event("change", { bubbles: true }));
                                }
            
                                if (container.dataset.callback) {
                                    const callbackFn = new Function("response", `${container.dataset.callback}(response)`);
                                    callbackFn(response);
                                }
                            },
                            'expired-callback': function () {
                                const formId = container.dataset.formId || "";
                                const inputId = container.dataset.inputId;
            
                                const input = formId
                                    ? document.querySelector(`#${formId} #${inputId}`)
                                    : document.querySelector(`#${inputId}`);
            
                                if (input) {
                                    input.value = "";
                                }
            
                                if (container.dataset.expiredCallback) {
                                    const expiredFn = new Function(`${container.dataset.expiredCallback}()`);
                                    expiredFn();
                                }
                            }
                        });
            
                        container.dataset.hcaptchaClientId = widgetId;
                    }
                });
            }
            JS
            , $view::POS_END);

        if (Yii::$app->request->isAjax) {
            $view->registerJs(<<<'JS'
                if (typeof grecaptcha !== "undefined") {
                    hcaptchaOnloadCallback();
                }
                JS
                , $view::POS_END
            );
        }

        $this->customFieldPrepare();
        echo Html::tag('div', '', $this->buildDivOptions());
    }

    protected function getInputId()
    {
        if (isset($this->widgetOptions['id'])) {
            return $this->widgetOptions['id'];
        }

        if ($this->hasModel()) {
            return Html::getInputId($this->model, $this->attribute);
        }

        return $this->id . '-' . $this->inputNameToId($this->name);
    }

    protected function getLanguageSuffix(): string
    {
        $currentAppLanguage = Yii::$app->language;
        $langExceptions = ['zh-CN', 'zh-TW', 'zh-TW'];

        if (!str_contains($currentAppLanguage, '-')) {
            return $currentAppLanguage;
        }

        if (\in_array($currentAppLanguage, $langExceptions)) {
            return $currentAppLanguage;
        }

        return \substr($currentAppLanguage, 0, \strpos($currentAppLanguage, '-'));
    }

    protected function customFieldPrepare(): void
    {
        $inputId = $this->getInputId();

        if ($this->hasModel()) {
            $inputName = Html::getInputName($this->model, $this->attribute);
        } else {
            $inputName = $this->name;
        }

        $options = $this->options;
        $options['id'] = $inputId;

        echo Html::input('hidden', $inputName, null, $options);
    }

    protected function buildDivOptions(): array
    {
        $divOptions = [
            'class' => 'h-captcha',
            'data-sitekey' => $this->siteKey
        ];
        $divOptions += $this->widgetOptions;

        if ($this->jsCallback) {
            $divOptions['data-callback'] = $this->jsCallback;
        }
        if ($this->jsExpiredCallback) {
            $divOptions['data-expired-callback'] = $this->jsExpiredCallback;
        }
        if ($this->jsErrorCallback) {
            $divOptions['data-error-callback'] = $this->jsErrorCallback;
        }
        if ($this->theme) {
            $divOptions['data-theme'] = $this->theme;
        }
        if ($this->type) {
            $divOptions['data-type'] = $this->type;
        }
        if ($this->size) {
            $divOptions['data-size'] = $this->size;
        }
        if ($this->tabIndex) {
            $divOptions['data-tabindex'] = $this->tabIndex;
        }

        if (isset($this->widgetOptions['class'])) {
            $divOptions['class'] = "{$divOptions['class']} {$this->widgetOptions['class']}";
        }
        $divOptions['data-input-id'] = $this->getInputId();

        if ($this->field && $this->field->form) {
            if ($this->field->form->options['id']) {
                $divOptions['data-form-id'] = $this->field->form->options['id'];
            } else {
                $divOptions['data-form-id'] = $this->field->form->id;
            }
        } else {
            $divOptions['data-form-id'] = '';
        }

        $divOptions['id'] = $this->getInputId() . '-captcha' .
            ($divOptions['data-form-id'] ? ('-' . $divOptions['data-form-id']) : '');

        return $divOptions;
    }

    protected function inputNameToId($name): string
    {
        return (string)\str_replace(['[]', '][', '[', ']', ' ', '.'], ['', '-', '-', '', '-', '-'], \strtolower($name));
    }

    /**
     * @throws InvalidConfigException
     */
    protected function configComponentProcess(): void
    {
        /** @var HCaptchaConfig $config */
        $config = Yii::$app->get($this->configComponentName, false);

        if (!$this->siteKey) {
            if ($config && $config->siteKey) {
                $this->siteKey = $config->siteKey;
                return;
            }
            throw new InvalidConfigException('Required `siteKey` param isn\'t set.');
        }

        if (!$this->jsApiUrl) {
            $this->jsApiUrl = HCaptchaConfig::JS_API_URL_DEFAULT;
            if ($config && $config->jsApiUrl) {
                $this->jsApiUrl = $config->jsApiUrl;
            }
        }
    }
}
