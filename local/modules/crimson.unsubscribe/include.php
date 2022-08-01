<?php

class CrimsonUnsubscribeHelper {

    private $module_id;

    public function __construct() {
        $this->module_id = basename(__DIR__);
    }

    private $settingsFieldName = 'UF_MAIL_PREFERENCES';

    /**
     * Код системного пользовательского поля
     * @return string
     */
    public function getSettingsFieldName() {
        return $this->settingsFieldName;
    }

    /**
     * Получить пользователя по email и токену отписки
     * @param string $email
     * @param string $token
     * @return array/false
     */
    public function getUserInfoByEmailAndToken($email, $token) {
        if ($user = $this->getUserInfo(0, $email)) {
            if ($user['token'] === $token) {
                return $user;
            }
        }
        return false;
    }

    /**
     * Сгенерировать токен для отписки
     * @param integer $userId
     * @param string $userEmail
     * @return string
     */
    public function buildToken($userId, $userEmail) {
        return hash('sha256', "{$userId}_{$userEmail}");
    }

    /**
     * Сделать ссылку на отписку
     * @param string $email
     * @param string $token
     * @return string
     */
    public function buildUnsubscribeLink($email, $token) {
        $query = "?" . http_build_query([
                    'email' => $email,
                    'token' => $token
        ]);
        return "https://" . ($_SERVER['SERVER_NAME'] ?? SITE_SERVER_NAME) . "/subscriptions/$query";
    }

    /**
     * Информация о пользователе
     * @param integer $userId
     * @param string $email
     * @return boolean
     */
    public function getUserInfo($userId, $email = "") {
        if (!$userId && !$email) {
            return false;
        }
        $filter = [];
        if ($userId) {
            $filter['=ID'] = $userId;
        }
        if ($email) {
            $filter['=EMAIL'] = $email;
        }
        $res = \Bitrix\Main\UserTable::getList([
                    "select" => [
                        "ID",
                        "EMAIL",
                        $this->getSettingsFieldName(),
                    ],
                    "filter" => $filter,
        ]);
        if ($arRes = $res->fetch()) {
            $ret = [
                'id' => $arRes['ID'],
                'email' => $arRes['EMAIL'],
                'token' => $this->buildToken($arRes['ID'], $arRes['EMAIL']),
                'unsubscribe' => explode(",", $arRes[$this->getSettingsFieldName()])
            ];
            $ret['link'] = $this->buildUnsubscribeLink($arRes['EMAIL'], $ret['token']);
            return $ret;
        }
        return false;
    }

    /**
     * Список групп типов событий из этого модуля
     * @return array
     */
    public function getUnsubscribeTypes() {
        $types = ['TYPE_SYSTEM', 'TYPE_PERSONAL', 'TYPE_SENDER'];
        $ret = [];
        foreach ($types as $type) {
            $ret[$type] = $this->getConfig($type);
        }
        return $ret;
    }

    /**
     * Подписан ли пользователь на конкретное событие
     * @param type $email
     * @param type $eventCode
     * @return boolean
     */
    public function isSubscribed($email, $eventCode) {
        $user = $this->getUserInfo(0, $email);
        if ($user) {
            // Берём группы отписок
            foreach ($this->getUnsubscribeTypes() as $type => $typeSettings) {
                // Берём группы отписок пользователя
                foreach ($user['unsubscribe'] as $unsubscribeType) {
                    // Если тип подходит
                    if ($unsubscribeType == $type) {
                        $types = explode(",", $typeSettings);
                        // И эвент найден
                        if (in_array($eventCode, $types)) {
                            // Отписался
                            return false;
                        }
                    }
                }
            }
        }
        return $user['link'];
    }

    /**
     * Отладка наличия подписки
     * @param string $email
     * @return array
     */
    public function checkUserAllEvents($email) {
        $ret = [];
        $ret[] = print_r($this->getUserInfo(0, $email), true);
        $service = new \SevenDeadFlies\Utils\SubscribeService();

        
        $ret[] = "Inner/OnBeforeEventAdd/OnBeforeEventSend - CODE - Название";
        $controller = new \SevenDeadFlies\Utils\SubscribeController();
        foreach ($this->getEmailTypesList() as $type => $typeTitle) {
            $checkInner = ($this->isSubscribed($email, $type) ? "Y" : "N");
            $params = ['EMAIL' => $email];
            $paramsEx = ['EVENT_NAME' => $type];
            $null = false;
            $checkBeforeEventAdd = ($controller->OnBeforeEventAdd($type, $null, $params, $null, $null, $null) ? "Y" : "N");
            $checkBeforeEventSend = ($controller->OnBeforeEventSend($params, $paramsEx) ? "Y" : "N");
            $checkInner = ($this->isSubscribed($email, $type) ? "Y" : "N");

            $ret[] = "$checkInner/$checkBeforeEventAdd/$checkBeforeEventSend - $type / $typeTitle";
        }
        $ret[] = print_r($service->getContactData($email), true);
        return $ret;
    }

    /**
     * Список всех типов событий
     * @return array
     */
    public function getEmailTypesList() {
        $ret = [];

        $rs = \Bitrix\Main\Mail\Internal\EventMessageTable::getList([
                    'select' => ['EVENT_NAME', 'TITLE' => 'ETYPE.NAME'],
                    'filter' => ['=ACTIVE' => 'Y'],
                    'order' => ['ETYPE.NAME' => 'ASC'],
                    'group' => ['EVENT_NAME'],
                    'runtime' => [
                        'ETYPE' => [
                            'data_type' => '\Bitrix\Main\Mail\Internal\EventTypeTable',
                            'reference' => [
                                '=this.EVENT_NAME' => 'ref.EVENT_NAME',
                            ],
                            'join_type' => 'left'
                        ]
                    ]
        ]);
        while ($arType = $rs->fetch()) {
            $ret[$arType['EVENT_NAME']] = $arType['TITLE'];
        }
        return $ret;
    }

    /**
     * Получить настройку
     * @param string $name
     * @return string
     */
    public function getConfig($name) {
        return \Bitrix\Main\Config\Option::get($this->module_id, $name);
    }

}

\Bitrix\Main\Loader::registerAutoLoadClasses(basename(__DIR__), array(
    '\SevenDeadFlies\Utils\SubscribeService' => 'include/SubscribeService.php', // Обработчик отписок
    '\SevenDeadFlies\Utils\SubscribeController' => 'include/SubscribeController.php', // Обработчик при отправке письма
));
