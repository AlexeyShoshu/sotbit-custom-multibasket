<?php

namespace SotbitCustom\Repositories;

use Bitrix\Main\Context;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Fuser;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\ElementPropertyTable;
use Sotbit\Multibasket\Entity\MBasketItemPropsTable;
use Sotbit\Multibasket\Entity\MBasketTable;
use Sotbit\Multibasket\Entity\MBasketItemTable;
use Sotbit\Multibasket\Models\MBasket;

class StoreRepository {
    static $amountCache = [];
    static $checkStandardWarehouse = false;

    public static function checkProductsStore(BasketItem $basketItem)
    {
        $fuser = new Fuser;
        $mBasketTable = new MBasketTable;
        $mBasketItemTable = new MBasketItemTable;
        $mBasketItemPropsTable = new MBasketItemPropsTable;

        $standardWarehouseId = self::getStandardStoreByProductId($basketItem->getField('PRODUCT_ID'));

        if (self::$checkStandardWarehouse && $standardWarehouseId) {
            BasketRepository::changeCurrentBasketByStoreId(self::getStandardStoreByProductId($basketItem->getField('PRODUCT_ID')));
        }

        $mBasket = MBasket::getCurrent(
            $fuser,
            $mBasketTable,
            $mBasketItemTable,
            $mBasketItemPropsTable,
            Context::getCurrent()
        );

        $storeId = (self::$checkStandardWarehouse)
            ? $standardWarehouseId
            : $mBasket->getStoreId();

        if (!$basketItem->canBuy()) {
            return;
        }

        $productId = (int)$basketItem->getField('PRODUCT_ID');
        if (!$productId) {
            return;
        }

        $storeAmount = self::getStoreAmount($productId, $storeId);

        if ((int)$storeAmount === 0) {
            if (!self::$checkStandardWarehouse) {
                self::$checkStandardWarehouse = true;
                return self::checkProductsStore($basketItem);
            } else {
                return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::ERROR,
                    new \Bitrix\Sale\ResultError(Loc::getMessage('SOTBIT_MULTIBASKET_ERROR_CHECK_QUANTITY')));
            }
        }

        return false;
    }

    private static function getStoreAmount(int $productId, int $storeId)
    {
        if (self::$amountCache[$productId]) {
            return self::$amountCache[$productId];
        } else {
            $storeProduct = \Bitrix\Catalog\StoreProductTable::getList(array(
                'filter' => ['=PRODUCT_ID' => $productId, '=STORE.ID' => $storeId],
                'select' => ['AMOUNT']
            ))->fetch();

            $storeAmount = $storeProduct !== false ? $storeProduct['AMOUNT'] : 0;
            self::setHitAmountCache($productId, $storeAmount);
            return $storeAmount;
        }
    }

    private static function setHitAmountCache(int $productId, $amount)
    {
        self::$amountCache[$productId] = $amount;
    }

    private static function getHitAmountCache(int $productId)
    {
        return self::$amountCache[$productId] ?: null;
    }

    public static function getStandardStoreByProductId(int $productId): int
    {
        $StandardWarehouseValue = ElementPropertyTable::getList([
            'select' => ['VALUE'],
            'filter' => [
                '=IBLOCK_ELEMENT_ID' => $productId,
                'PROPERTY.CODE' => 'SOTBIT_STANDARD_WAREHOUSE',
            ],
            'runtime' => [
                new ReferenceField(
                    'PROPERTY',
                    PropertyTable::class,
                    [
                        '=this.IBLOCK_PROPERTY_ID' => 'ref.ID'
                    ]
                )
            ]
        ])->fetch()['VALUE'];

        return (int)$StandardWarehouseValue;
    }
}