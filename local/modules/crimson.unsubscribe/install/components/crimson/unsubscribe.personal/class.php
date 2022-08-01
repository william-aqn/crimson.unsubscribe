<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class CrimsonUnsubscribe extends \CBitrixComponent {

    private $userInfo = false;

    public function getUserInfo() {
        return $this->userInfo;
    }

    public function onPrepareComponentParams($arParams) {
        global $USER;
        $arParams = [
            "EMAIL" => filter_var($_GET['email'], FILTER_VALIDATE_EMAIL),
            "TOKEN" => preg_replace("[^0-9a-zA-Z]", "", $_GET['token']),
            "USER_ID" => (int) $USER->GetID(),
        ];
        return $arParams;
    }

    public function buildItems() {
        $this->arResult = ['ITEMS' => []];

        if (!\Bitrix\Main\Loader::includeModule('crimson.unsubscribe')) {
            return false;
        }

        $helper = new \CrimsonUnsubscribeHelper();
        if ($this->arParams["USER_ID"] > 0) {
            // Если авторизован
            $this->userInfo = $helper->getUserInfo($this->arParams["USER_ID"]);
        } elseif ($this->arParams["EMAIL"] && $this->arParams["TOKEN"]) {
            // Если есть токен
            $this->userInfo = $helper->getUserInfoByEmailAndToken($this->arParams["EMAIL"], $this->arParams["TOKEN"]);
        }

        // Заполняем чекбоксы для шаблона
        if ($this->userInfo) {
            foreach ($helper->getUnsubscribeTypes() as $type => $typeSettings) {
                $this->arResult['ITEMS'][$type] = ['CODE' => $type, 'SELECTED' => (in_array($type, $this->userInfo['unsubscribe']) ? "N" : "Y")];
            }
            return $this->arResult['ITEMS'];
        }
        return false;
    }

    /**
     * Выводим данные
     * @return array
     */
    public function executeComponent() {
        if (!$this->buildItems()) {
            $this->arResult['ERROR'] = 'Error';
        }

        $this->includeComponentTemplate();
        return $this->arResult;
    }

}
