hCaptcha widget for Yii2
================================
This package based on archived [Yii2 Recaptcha Widget](https://github.com/himiklab/yii2-recaptcha-widget) and adapted for hCaptcha.

[![Packagist](https://img.shields.io/packagist/dt/walkboy/yii2-hcaptcha.svg)]() [![Packagist](https://img.shields.io/packagist/v/walkboy/yii2-hcaptcha.svg)]()  [![license](https://img.shields.io/badge/License-MIT-yellow.svg)]()


Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require "walkboy/yii2-hcaptcha" "*"
```

or add

```json
"walkboy/yii2-hcaptcha" : "*"
```

to the `require` section of your application's `composer.json` file.

* [Sign up for an hCaptcha API keys](https://dashboard.hcaptcha.com/signup).

* Configure the component in your configuration file (web.php or main.php). The parameters siteKey and secret are optional.
But if you leave them out you need to set them in every validation rule and every view where you want to use this widget.
If a siteKey or secret is set in an individual view or validation rule that would overrule what is set in the config.

```php
'components' => [
    'hCaptcha' => [
        'class' => \walkboy\hcaptcha\HCaptchaConfig',
        'siteKey' => 'your siteKey',
        'secret' => 'your secret',
    ],
    ...
],
```

* Add `HCaptchaValidator` in your model, for example:

```php
public $verifyKey;

public function rules()
{
  return [
      // ...
      [['verifyKey'], \walkboy\hcaptcha\HCaptchaValidator::class,
        'secret' => 'your secret key', // unnecessary if hCaptcha is already configured
        'uncheckedMessage' => 'Please confirm that you are not a robot.'],
  ];
}
```


Usage
-----
For example:

```php
<?= $form->field($model, 'verifyKey')->widget(
    \walkboy\hcaptcha\HCaptcha2::className(),
    [
        'siteKey' => 'your siteKey', // unnecessary is hCaptcha component was set up
    ]
) ?>
```

or without model

```php
<?= \walkboy\hcaptcha\HCaptcha2::widget([
    'name' => 'verifyKey',
    'siteKey' => 'your siteKey', // unnecessary is hCaptcha component was set up
    'widgetOptions' => ['class' => 'col-sm-offset-3'],
]) ?>
```

* NOTE: Please disable ajax validation for hCaptcha field!

Resources
---------
* [hCaptcha Docs](https://docs.hcaptcha.com/)
