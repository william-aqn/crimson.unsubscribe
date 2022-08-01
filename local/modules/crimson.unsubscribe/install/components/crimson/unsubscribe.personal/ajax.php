<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

class CrimsonUnsubscribeAjax extends \Bitrix\Main\Engine\Controller {

    /**
     * Проверка post запроса + сессии
     */
    protected function getDefaultPreFilters() {
        return [
            new \Bitrix\Main\Engine\ActionFilter\HttpMethod(
                    array(\Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST)
            ),
            new \Bitrix\Main\Engine\ActionFilter\Csrf(),
        ];
    }

    /**
     * Обработчик отписки у пользователя
     * @param array $types
     */
    public function unsubscribeAction($types) {
        $types = explode(",", $types);
        // Переиспользуем логику компонента
        \CBitrixComponent::includeComponentClass("crimson:unsubscribe.personal");

        $class = new \CrimsonUnsubscribe();
        $class->arParams = $class->onPrepareComponentParams([]);

        if ($items = $class->buildItems()) {
            if ($userInfo = $class->getUserInfo()) {

                // Проверяем входящие типы отписок
                $updateTypes = [];
                foreach ($types as $type) {
                    if (array_key_exists($type, $items)) {
                        $updateTypes[$type] = $type;
                    }
                }

                // Управление чёрным списком в модуле sender
                $service = new \SevenDeadFlies\Utils\SubscribeService();
                if ($updateTypes['TYPE_SENDER']) {
                    $service->unsubscribe($userInfo['email']);
                } else {
                    $service->subscribe($userInfo['email']);
                }

                // Обновляем настройки пользователя
                $helper = new \CrimsonUnsubscribeHelper();
                $user = new \CUser;
                $updFields = [$helper->getSettingsFieldName() => implode(",", $updateTypes)];
                if (!$user->Update($userInfo['id'], $updFields)) {
                    $this->error($user->LAST_ERROR);
                } else {
                    return ['msg' => 'ok'];
                }
            } else {
                $this->error('user error');
            }
        }
        $this->error('error');
    }

    private function error($msg) {
        throw new \Exception($msg);
    }

}
