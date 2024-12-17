<?php

namespace Custom\RestApi\Controllers;

class ExampleController
{
    public function __invoke()
    {
        return ['Index_Controller' => 'GET'];
    }

    /**
     * Образец метода, который находится в файле .../php_interface/awelite.restapi/routes.php также зкомментирован в качестве
     * образца для добавления нового маршрута
     */
    /*public function stub():array
    {
        return['EXAMPLE' => 'Test Success', 'METHOD' => 'stub'];
    }*/

}