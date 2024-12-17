<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class ExchangeComponent extends CBitrixComponent
{
    protected $userObj;

	public function executeComponent()
	{
	    global $USER;

	    $this->userObj = $USER;

	    if (! \Bitrix\Main\Loader::includeModule('pvp.exchangev1')) {
	        throw new \Exception('Модуль pvp.exchangev1 не установлен!');
        }

        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

	    if ($this->arParams["SEF_MODE"] == "Y") {
            $arVariables = [];
            $arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates([], $this->arParams["SEF_URL_TEMPLATES"]);
            $arVariableAliases = CComponentEngine::MakeComponentVariableAliases([], $this->arParams["VARIABLE_ALIASES"]);

            $componentPage = CComponentEngine::ParseComponentPath(
                $this->arParams["SEF_FOLDER"],
                $arUrlTemplates,
                $arVariables
            );

            CComponentEngine::InitComponentVariables($componentPage, [], $arVariableAliases, $arVariables);
        } else {
            Throw new \Exception('ЧПУ обязательно для работы компонента!');
        }

	    $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

	    if (! $request->isHttps()) {
	        $this->accessDenied(GetMessage('HTTPS_ONLY'));
        }

	    if (empty($arVariables['AUTH'])) {
            $this->accessDenied(GetMessage('TOKEN_NOT_FOUND'));
        }

        if (! empty($this->arParams['USER_TOKEN_FIELD'])) {
            $sortBy = 'ID';
            $sortOrder = 'ASC';
            $res = $this->userObj::GetList(
                $sortBy, $sortOrder,
                [$this->arParams['USER_TOKEN_FIELD'] => $arVariables['AUTH']],
                [
                    'SELECT' => ['ID', 'LOGIN', 'EMAIL', $this->arParams['USER_TOKEN_FIELD']],
                ],
            );

            $currentUser = [];
            while ($userAr = $res->GetNext()) {
                if (empty($currentUser)) {
                    $currentUser = $userAr;
                } else {
                    $this->accessDenied(GetMessage('DUPLICATE_KEY'));
                }
            }

            $this->userObj->Authorize($currentUser['ID']);
        } else {
            if (!$this->arParams['AUTH_STRING'] || $this->arParams['AUTH_STRING'] != $arVariables['AUTH']) {
                $this->accessDenied();
            }
        }

        $controller = \PVP\ExchangeV1\MethodFactory::create($arVariables['CONTROLLER'], $arVariables['METHOD']);

        $controller->execute();

        $this->arResult = $controller->getResult();

        $this->sendResponse($controller->getResultCode());
	}



	protected function sendResponse($code = 200)
    {
        global $APPLICATION;

        $this->setFrameMode(false);
        define("BX_COMPRESSION_DISABLED", true);

        ob_start();
        $this->IncludeComponentTemplate();
        $json = ob_get_contents();
        $APPLICATION->RestartBuffer();

        while(ob_end_clean());

        http_response_code($code);
        header('Content-Type: application/json; charset='.LANG_CHARSET);

        echo $json;

        CMain::FinalActions();

        $this->userObj->Logout();
    }

    protected function accessDenied($message = false)
    {
        $this->arResult['ERROR'] = $message ? : GetMessage('ACCESS_DENIED');

        $this->sendResponse(403);
        die();
    }
}