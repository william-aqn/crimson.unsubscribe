<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (isset($arResult['ERRORS'])) {
    echo '<div style="color: red;">' . $arResult['ERRORS'] . '</div>';
    return;
}
?>
<div id="subscriptions" class="mailing-lists">
    <h3><?= Loc::getMessage('TYPES_OF_MAILING') ?></h3>
    <div class="mailing-lists__item-wrap">
        <? foreach ($arResult["ITEMS"] as $arItem): ?>
            <div <?= $id ?> class="mailing-lists__item">
                <label class="check noselect">
                    <input name="check_square" type="checkbox" data-type="<?= $arItem['CODE'] ?>" <?= ($arItem['SELECTED'] == "Y" ? "checked" : "") ?>>
                    <i class="fa fa-1 fa-square-o"></i>
                    <i class="fa fa-2 fa-check-square"></i>
                    <span></span>
                </label>
                <div class="mailing-lists__text-wrap">
                    <p class="mailing-lists__text-title">
                        <?= Loc::getMessage("{$arItem["CODE"]}") ?>
                    </p>
                    <p class="mailing-lists__text">
                        <?= Loc::getMessage("{$arItem["CODE"]}_DESCR") ?>
                    </p>
                </div>
            </div>
        <? endforeach; ?>
    </div>
    <!--<button type="submit" class="main_btn disabledInput" disabled="">Сохранить</button>-->
</div>


<script>
<? if (!empty($arParams["EMAIL"]) && !empty($arParams["TOKEN"])): ?>
        let extParams = {
            email: '<?= $arParams["EMAIL"] ?>',
            token: '<?= $arParams["TOKEN"] ?>'
        };
<? else: ?>
        let extParams = false;
<? endif; ?>
    let s_service = new SubscribeService('#subscriptions', extParams);
</script>