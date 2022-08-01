<?php

namespace SevenDeadFlies\Utils;

class SubscribeService {

    public function __construct() {
        $this->fields['rubrics'] = $this->getRubrics();
    }

    public function subscribe($email) {
        $arContact = $this->getContactData($email);
        foreach ($this->fields['rubrics'] as $item) {
            $arContact['object']->subscribe($item);
        }
        \Bitrix\Sender\ContactTable::update($arContact['id'], ['BLACKLISTED' => 'N']);
    }

    public function unsubscribe($email) {
        $arContact = $this->getContactData($email);
        foreach ($arContact['data']['SUB_LIST'] as $item) {
            $arContact['object']->unsubscribe($item);
        }
        \Bitrix\Sender\ContactTable::update($arContact['id'], ['BLACKLISTED' => 'Y']);
    }

    public function getContactData($email) {
        if (\Bitrix\Main\Loader::includeModule("sender")) {
            $arContact['id'] = \Bitrix\Sender\ContactTable::addIfNotExist(['EMAIL' => $email]);
            $arContact['object'] = new \Bitrix\Sender\Entity\Contact($arContact['id']);
            $arContact['data'] = $arContact['object']->loadData($arContact['id']);
            return $arContact;
        }
        return FALSE;
    }

    public function getRubrics() {
        if (!\Bitrix\Main\Loader::includeModule("subscribe")) {
            return false;
        }
        $rsRubric = \CRubric::GetList([], ["ACTIVE" => "Y"]);
        while ($arRubric = $rsRubric->GetNext()) {
            $arRubrics[] = $arRubric['ID'];
        }
        return $arRubrics;
    }

}
