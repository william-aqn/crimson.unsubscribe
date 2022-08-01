<?php

namespace SevenDeadFlies\Utils;

class SubscribeController {

    /**
     * Отменяем отправку + не захламляем таблицу b_events
     * @param string $event
     * @param string $lid
     * @param array $arFields
     * @param integer $messageId
     * @param array $files
     * @param string $languageId
     * @return boolean
     */
    function OnBeforeEventAdd(&$event, &$lid, &$arFields, &$messageId, &$files, &$languageId) {
        $helper = new \CrimsonUnsubscribeHelper();
        if ($helper->isSubscribed($arFields["EMAIL_TO"] ?? $arFields["EMAIL"], $event) !== false) {
            return true;
        } return false;
    }

    /**
     * Добавляем ссылку на отписку и отменяем отправку если что то просочилось
     * @param array $arFields
     * @param array $arTemplate
     * @return boolean
     */
    function OnBeforeEventSend(&$arFields, $arTemplate) {
        $helper = new \CrimsonUnsubscribeHelper();
        if ($unsubscribeLink = $helper->isSubscribed($arFields["EMAIL_TO"] ?? $arFields["EMAIL"], $arTemplate["EVENT_NAME"])) {
            $arFields["UNSUBSCRIBE_LINK"] = $unsubscribeLink;
            return true;
        } return false;
    }

}
