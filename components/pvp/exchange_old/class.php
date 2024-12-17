<?php

use PVP\Exchange\authorizers\AuthorizerInteface;
use PVP\Exchange\authorizers\JsonWebTokenAuthorizer;
use PVP\Exchange\authorizers\NullObjectAuthorizer;
use PVP\Exchange\authorizers\UserFieldAuthorizer;
use PVP\Exchange\Controller\Authorization\JWTAuth;
use PVP\Exchange\Controller\Authorization\UFAuth;
use PVP\Exchange\ErrorManager;
use PVP\Exchange\Response\Response;
use PVP\Exchange\Controller\ControllerInterface;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class ExchangeComponent extends CBitrixComponent
{
    protected Response $response;

    protected ControllerInterface $controller;
    protected string $method;

    protected AuthorizerInteface $authorizer;

    protected CUser $user;

    protected array $controllerAliases = [];

    protected ErrorManager $errorManager;

	public function executeComponent()
	{
	    if (! \Bitrix\Main\Loader::includeModule('pvp.exchange')) {
	        throw new \Exception('Модуль pvp.exchange не установлен!');
        }

        $this->initComponentTemplate('');

        $exchangeComponent = \PVP\Exchange\ExchangeComponent::getInstance();
        $exchangeComponent->init($this);

        $this->initAuthorization();
        $this->authorize();

        try {
            $this->init();
        } catch (\Throwable $e) {
            $this->sendException($e);
        }

        if ($this->errorManager->hasErrors()) {
            $this->sendResponse();
        }


        try {
            $this->controller->{$this->method}();
        } catch (\Throwable $e) {
            $this->sendException($e);
        }

        $server = \Bitrix\Main\Application::getInstance()->getContext()->getServer();
        $componentPagesPath = $server->getDocumentRoot() . DIRECTORY_SEPARATOR
            . $this->getPath() . DIRECTORY_SEPARATOR . 'componentPages';

        global $APPLICATION;

        foreach ($exchangeComponent->getComponentPages() as $pageData) {
            $page = str_replace('.', DIRECTORY_SEPARATOR, $pageData['page']);
            $path = $componentPagesPath . DIRECTORY_SEPARATOR . $page . '.php';

            $componentPage = new \PVP\Exchange\ComponentPage($path, $pageData['params']);
            $componentPage->render();
        }

        if ($this->errorManager->hasErrors()) {
            $this->response->setStatusCode(Response::STATUS_ERROR);
        }

        $this->sendResponse();
	}


    protected function initAuthorization()
    {
        global $USER;

        $this->user = $USER;

        switch($this->arParams['AUTH_METHOD']) {
            case 'JWT':
                $this->addAlias('PVP\\Exchange\\Controller\\Authorization', JWTAuth::class);
                $this->authorizer = new JsonWebTokenAuthorizer();
                break;
            case 'UF':
                $this->addAlias('PVP\\Exchange\\Controller\\Authorization', UFAuth::class);
                $this->authorizer = new UserFieldAuthorizer();
                break;
            default:
                $this->authorizer = new NullObjectAuthorizer();
        }


    }


    protected function init(): void
    {
        $this->response = Response::getInstance();
        $this->errorManager = ErrorManager::getInstance();

        if ('Y' == $this->arParams['DEBUG_MODE']) {
            $this->errorManager->setDebugMode(true);
        }

        $this->checkProtocol();

        if ($this->errorManager->hasErrors()) {
            return;
        }

        $this->initController();
    }



    protected function checkProtocol(): bool
    {
        if ($this->request->isHttps()) {
            return true;
        }

        $this->setForbidden();
        $this->errorManager->addError(GetMessage('PVP_EXCHANGE_HTTPS_ONLY'));
    }

    protected function initController(): void
    {
        $componentVariables = $this->getComponentVariables();

        if (empty($componentVariables['CONTROLLER']) && empty($componentVariables['METHOD'])) {
            if ('Y' == $this->arParams['DEBUG_MODE']) {
                $this->errorManager->addError(GetMessage('PVP_EXCHANGE_ERR_PARAMS_NOT_FOUND'));
            }

            $this->setBadRequest();

            return;
        }

        $method = $componentVariables['METHOD'];

        /** @var class-string<ControllerInterface> $controllerClass */
        if ($controllerClass = $this->findControllerClass($componentVariables['CONTROLLER'])) {
            if ($controllerClass::needAuthorization()) {
                if (! $this->user->IsAuthorized()) {
                    $this->setUnAuthorized();
                    $this->sendResponse();
                } elseif ($controllerClass::needAdminRights() && ! $this->user->IsAdmin()) {
                    $this->errorManager->addError(GetMessage('PVP_EXCHANGE_ERR_PARAMS_NEED_MORE_RIGHTS'));
                    $this->setUnAuthorized();
                    $this->sendResponse();
                }
            }

            $controller = new $controllerClass;

            if ($this->checkMethod($controller, $method)) {
                $this->method = $method;
                $this->controller = $controller;

                return;
            }
        }

        $this->setNotFound();
    }

    protected function getComponentVariables(): array
    {
        if ('Y' != $this->arParams["SEF_MODE"]) {
            throw new \Exception('ЧПУ обязательно для работы компонента!');
        }

        $arVariables = [];
        $arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates([], $this->arParams["SEF_URL_TEMPLATES"]);

        $componentPage = CComponentEngine::ParseComponentPath(
            $this->arParams["SEF_FOLDER"],
            $arUrlTemplates,
            $arVariables
        );

        CComponentEngine::InitComponentVariables($componentPage, [], [], $arVariables);

        return $arVariables;
    }

    protected function findControllerClass(string $controller): string
    {
        $controllerClass = 'PVP\\Exchange\\Controller\\' . ucfirst($controller);

        if ($this->hasAlias($controllerClass)) {
            $controllerClass = $this->getAlias($controllerClass);
        }


        if (class_exists($controllerClass)) {
            return $controllerClass;
        }

        return '';
    }

    public function setBadRequest(): void
    {
        $this->response->setStatusCode(Response::STATUS_ERROR);
        $this->errorManager->addError(GetMessage('PVP_EXCHANGE_ERR_BAD_REQUEST'));
    }

	public function sendResponse()
    {
        global $APPLICATION;

        $this->setFrameMode(false);
        define("BX_COMPRESSION_DISABLED", true);

        $formattedResponse = $this->response->getFormattedData( new \PVP\Exchange\Response\Format\Json());

        $this->arResult = $formattedResponse;

        ob_start();
        $this->IncludeComponentTemplate();
        $output = ob_get_contents();

        $APPLICATION->RestartBuffer();

        while(ob_end_clean());

        http_response_code($this->response->getStatusCode());
        header('Content-Type: application/json; charset=' . LANG_CHARSET);

        $this->user->Logout();

        CMain::FinalActions($output);
    }

    protected function setUnAuthorized(): void
    {
        $this->response->setStatusCode(Response::STATUS_UNAUTHORIZED);
        $this->errorManager->addError(GetMessage('PVP_EXCHANGE_ERR_UNAUTHORIZED'));
    }

    protected function setForbidden(): void
    {
        $this->response->setStatusCode(Response::STATUS_FORBIDDEN);
        $this->errorManager->addError(GetMessage('PVP_EXCHANGE_ERR_ACCESS_DENIED'));
    }


    protected function setNotFound(): void
    {
        $this->response->setStatusCode(Response::STATUS_NOT_FOUND);
        $this->errorManager->addError(GetMessage('PVP_EXCHANGE_ERR_METHOD_NOT_FOUND'));
    }

    protected function sendException(\Throwable $e)
    {
        $errorInfo = $e->getMessage();

        if ('Y' == $this->arParams['DEBUG_MODE']) {
            $errorInfo .= '  ' . PHP_EOL . 'File:' . $e->getFile() . ' Line:' . $e->getLine();
        }

        $this->errorManager->addError($errorInfo);
        $this->response->setStatusCode(Response::STATUS_INTERNAL_ERROR);
        $this->sendResponse();
    }

    protected function addAlias(string $controller, string $alias)
    {
        $this->controllerAliases[$controller] = $alias;
    }

    protected function getAlias(string $controller): string
    {
        return $this->controllerAliases[$controller];
    }

    protected function hasAlias(string $controller): bool
    {
        return ! empty($this->controllerAliases[$controller]);
    }

    protected function authorize()
    {
        $matches = [];
        if (preg_match('/Bearer\s(\S+)/', $this->request->getHeader('Authorization'), $matches)) {
            $token = $matches[1];

            $this->authorizer->authorize($token, $this->arParams);
        }
    }

    protected function checkMethod(ControllerInterface $controller, string $method): bool
    {
        if (! method_exists($controller, $method)) {
            return false;
        }

        $httpMethod = $this->request->isPost() ? 'POST' : 'GET';

        return in_array($method, $controller->getApiMethodList($httpMethod));
    }

}