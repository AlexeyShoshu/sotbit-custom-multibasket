<?

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;

use Sotbit\Multibasket\Listeners\CheckStoreListener;
use Sotbit\Multibasketcustomevents\Listeners\CustomCheckStoreListener;

class sotbit_multibasketcustomevents extends CModule
{
    var $MODULE_ID = "sotbit.multibasketcustomevents";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;

    public function __construct()
    {
        $arModuleVersion = array();
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path . "/version.php");
        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
        $this->MODULE_NAME = "Mодуль для кастомизации событий в модуле Сотбит: Мультикорзина";
        $this->MODULE_DESCRIPTION = "После установки, события модуля Сотбит: Мультикорзина checkAddedItems
        и checkUpdateItems будут заменены на кастомные";

        $this->arEventStandard = [
            ['fromModuleId' => 'sale', 'eventType' => 'OnSaleBasketItemRefreshData', 'toModuleId' => 'sotbit.multibasket', 'toClass' => CheckStoreListener::class, 'toMethod' => 'checkAddedItems'],
            ['fromModuleId' => 'sale', 'eventType' => 'OnSaleBasketBeforeSaved', 'toModuleId' => 'sotbit.multibasket', 'toClass' => CheckStoreListener::class, 'toMethod' => 'checkUpdateItems'],
        ];

        $this->arEventCustom = [
            ['fromModuleId' => 'sale', 'eventType' => 'OnSaleBasketItemRefreshData', 'toModuleId' => $this->MODULE_ID, 'toClass' => CustomCheckStoreListener::class, 'toMethod' => 'checkAddedItems'],
            ['fromModuleId' => 'sale', 'eventType' => 'OnSaleBasketBeforeSaved', 'toModuleId' => $this->MODULE_ID, 'toClass' => CustomCheckStoreListener::class, 'toMethod' => 'checkUpdateItems'],
        ];
    }

    public function DoInstall()
    {
        global $APPLICATION;

        if (!Loader::includeModule('sotbit.multibasket')) {
            $APPLICATION->ThrowException('Модуль sotbit.multibasket не установлен');
            return false;
        }

        global $DOCUMENT_ROOT, $APPLICATION;
        $this->InstallEvents();
        RegisterModule("sotbit.multibasketcustomevents");
        $APPLICATION->IncludeAdminFile("Установка модуля sotbit.multibasketcustomevents", $DOCUMENT_ROOT . "/local/modules/sotbit.multibasketcustomevents/install/step.php");
    }

    public function DoUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;
        $this->UnInstallEvents();
        UnRegisterModule("sotbit.multibasketcustomevents");
        $APPLICATION->IncludeAdminFile("Деинсталляция модуля sotbit.multibasketcustomevents", $DOCUMENT_ROOT . "/local/modules/sotbit.multibasketcustomevents/install/unstep.php");
    }

    public function InstallEvents()
    {
        foreach ($this->arEventCustom as $event) {
            EventManager::getInstance()->registerEventHandler(
                $event['fromModuleId'],
                $event['eventType'],
                $event['toModuleId'],
                $event['toClass'],
                $event['toMethod'],
            );
        }

        foreach ($this->arEventStandard as $event) {
            EventManager::getInstance()->unRegisterEventHandler(
                $event['fromModuleId'],
                $event['eventType'],
                $event['toModuleId'],
                $event['toClass'],
                $event['toMethod'],
            );
        }
    }

    public function UnInstallEvents()
    {
        foreach ($this->arEventCustom as $event) {
            EventManager::getInstance()->unRegisterEventHandler(
                $event['fromModuleId'],
                $event['eventType'],
                $event['toModuleId'],
                $event['toClass'],
                $event['toMethod'],
            );
        }

        foreach ($this->arEventStandard as $event) {
            EventManager::getInstance()->registerEventHandler(
                $event['fromModuleId'],
                $event['eventType'],
                $event['toModuleId'],
                $event['toClass'],
                $event['toMethod'],
            );
        }
    }

}

?>