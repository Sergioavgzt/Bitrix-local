<?php

use Bitrix\Main\Application;
use PVP\Exchange\ErrorManager;
use PVP\SmsAuth\SmsAuth;

class SmsAuthComponent extends CBitrixComponent
{
    protected string $moduleCode = 'pvp.smsauth';
    protected SmsAuth $smsAuthObj;
    protected ErrorManager $errorManager;
    protected \Bitrix\Main\Session\SessionInterface $session;

    const CHECK_COUNT = 'pvpSmsAuthCheckCount';
    const PHONE = 'pvpSmsAuthPhone';
    const BLOCK_TIME = 'pvpSmsAuthBlockTime';
    const LAST_REQUEST_TIME = 'pvpSmsAuthLastRequestTime';
    public function __construct($component = null)
    {
        parent::__construct($component);

        if (! \Bitrix\Main\Loader::includeModule($this->moduleCode)) {
            throw new \Exception('Модуль не найден: ' . $this->moduleCode);
        }

        if (! \Bitrix\Main\Loader::includeModule('pvp.exchange')) {
            throw new \Exception('Модуль не найден: ' . 'pvp.exchange');
        }

        $this->smsAuthObj = new SmsAuth();
        $this->errorManager = ErrorManager::getInstance();
        $this->session = Application::getInstance()->getSession();
    }

    public function executeComponent()
	{

        \CJSCore::Init(['pvp_cleave']);

        $this->smsAuthObj->setMessageLimit($this->arParams['MESSAGE_LIMIT'])
            ->setSendTimeout($this->arParams['SEND_TIMEOUT'])
            ->setCodeTtl($this->arParams['CODE_TTL'])
            ->setResetLimitsTimeout($this->arParams['RESET_TIMEOUT'])
            ->setSender(new \PVP\SmsAuth\Sender\SmscSender);

        $request = Application::getInstance()->getContext()->getRequest();

        if ($this->session->has(self::PHONE)) {
            $this->arResult['PHONE'] = $this->session->get(self::PHONE);
        }

        //П
        if ($request->isPost() && 'pvp.smsauth' == $request->getPost('mode')) {
            if ('resendCode' == $request->getPost('action')) {
                if (empty($this->arResult['PHONE'])) {
                    $this->errorManager->addError(GetMessage('SMSAUTH_PHONE_SESSION_EXPIRED_ERROR'));
                    $this->showSend();

                    return false;
                }

                if ($this->smsAuthObj->sendCode($this->arResult['PHONE'])) {
                    $this->showConfirm();

                    return true;
                }
            }

            if ('resetPhone' == $request->getPost('action')) {
                $this->arResult['PHONE'] = '';
                $this->session->set(self::PHONE, null);

                $this->showSend();

                return true;
            }

            if ('send' == $request->getPost('action') && $request->getPost('phone')) {
                $this->arResult['PHONE'] = $request->getPost('phone');

                if ($this->smsAuthObj->sendCode($this->arResult['PHONE'])) {
                    $this->session->set(self::PHONE, $this->arResult['PHONE']);
                    $this->showConfirm();

                    return true;
                }
            }

            if ('checkCode' == $request->getPost('action') && $request->getPost('code')) {
                $code = $request->getPost('code');
                $phone = $this->arResult['PHONE'];

                if (! $phone) {
                    $this->errorManager->addError(GetMessage('SMSAUTH_PHONE_SESSION_EXPIRED_ERROR'));
                } else {
                    if ($this->smsAuthObj->login($phone, $code)) {
                        $this->showSuccess();

                        return true;
                    }

                    $this->arResult['CODE'] = $code;

                    $this->showConfirm();
                    return false;
                }
            }

            if ($this->errorManager->hasErrors()) {
                $this->setLabel('');
                $this->initComponentTemplate('send');
                $this->showComponentTemplate();

                return true;
            } else {
                http_response_code(403);
                return false;
            }
        }

        $this->arResult['MESSAGE']['CLASS'] = '';
        $this->arResult['MESSAGE']['TEXT'] = GetMessage('SMSAUTH_LABLE_INPUT_PHONE');

        $this->initComponentTemplate();
        $this->showComponentTemplate();
	}

    protected function showConfirm()
    {
        $this->setLabel(GetMessage('SMSAUTH_LABLE_INPUT_CODE'));

        $this->arResult['NEXT_SEND_TIMEOUT'] = $this->smsAuthObj->getNextSendTimeout($this->arResult['PHONE']);

        $this->initComponentTemplate('confirm');
        $this->showComponentTemplate();
    }

    protected function showSuccess()
    {
        $this->setLabel(GetMessage('SMSAUTH_LABLE_INPUT_CODE'));

        $this->initComponentTemplate('success');
        $this->showComponentTemplate();
    }

    protected function showSend()
    {
        $this->arResult['MESSAGE']['CLASS'] = '';
        $this->arResult['MESSAGE']['TEXT'] = GetMessage('SMSAUTH_LABLE_INPUT_PHONE');

        $this->initComponentTemplate('send');
        $this->showComponentTemplate();
    }

    protected function setLabel($message)
    {
        if ($this->errorManager->hasErrors()) {
            $this->arResult['MESSAGE']['CLASS'] = 'error';
            $this->arResult['MESSAGE']['TEXT'] = join('<br>', $this->errorManager->getErrorMessages());

            return;
        }

        $this->arResult['MESSAGE']['CLASS'] = '';
        $this->arResult['MESSAGE']['TEXT'] = $message;
    }
}