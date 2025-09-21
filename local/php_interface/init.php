<?php
use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;

use SotbitCustom\Repositories\BasketRepository;

Loader::registerNamespace(
    "SotbitCustom\\",
    __DIR__ . '/include/lib'
);

EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleBasketItemRefreshData',
    [BasketRepository::class, 'checkAddedItems'],
    false,
    10
);