<?php

namespace SotbitCustom\Repositories;

use Bitrix\Main\Context;
use Bitrix\Main\Event;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Fuser;
use Sotbit\Multibasket\Entity\MBasketTable;
use Sotbit\Multibasket\Helpers\Config;
use Sotbit\Multibasket\Models\MBasketCollection;

class BasketRepository {
    /**
     * event handler OnSaleBasketItemRefreshData to check if the item being added
     *
     * @param Event $event
     */
    public static function checkAddedItems(Event $event)
    {
        $context = Context::getCurrent();

        if (!Config::moduleIsEnabled($context->getSite())) {
            return;
        }

        if (Config::getWorkMode($context->getSite()) === 'default') {
            return;
        }

        if (MBasketCollection::ignorEvent()) {
            return;
        }

        /** @var BasketItem */
        $basketItem = $event->getParameter('ENTITY');

        if ($basketItem->getProvider() === '\Bitrix\Sale\ProviderAccountPay') {
            return;
        }

        if (!$basketItem->getField('PRODUCT_ID')) {
            return;
        }

        return StoreRepository::checkProductsStore($basketItem);

    }

    public static function changeCurrentBasketByStoreId(int $storeId) : bool
    {
        $fuserId = Fuser::getId();

        $baskets = MBasketTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=FUSER_ID' => $fuserId,
                '=CURRENT_BASKET' => 1
            ]
        ]);

        while ($basket = $baskets->fetch()) {
            MBasketTable::update($basket['ID'], ['CURRENT_BASKET' => 0]);
        }

        $basketId = MBasketTable::getList([
            'select' => ['ID'],
            'filter' => ['=FUSER_ID' => $fuserId, '=STORE_ID' => $storeId],
        ])->fetch()['ID'];

        $result = MBasketTable::update($basketId, [
            'CURRENT_BASKET' => 1,
        ]);

        return $result->isSuccess();
    }
}