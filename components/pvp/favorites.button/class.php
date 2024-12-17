<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class FavoritesButtonComponent extends CBitrixComponent
{
	public function executeComponent()
	{
        $this->initComponentTemplate();
        $this->showComponentTemplate();
	}
}