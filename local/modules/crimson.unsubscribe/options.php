<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
\Bitrix\Main\Loader::includeModule(basename(__DIR__));

/**
 * https://dev.1c-bitrix.ru/community/webdev/user/203730/blog/13249/
 */
class CrimsonUnsubscribeOptions {

    private $module_id;

    public function __construct() {
        $this->module_id = \basename(__DIR__);

        global $APPLICATION;
        $AUTH_RIGHT = $APPLICATION->GetGroupRight($this->module_id);

        if ($AUTH_RIGHT <= "D") {
            $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
        }

        $helper = new \CrimsonUnsubscribeHelper();

        if ($_REQUEST['email']) {
            $APPLICATION->RestartBuffer();
            echo implode("\n\n", $helper->checkUserAllEvents($_REQUEST['email']));
            die();
        }

        $listEmailTypeEvents = $helper->getEmailTypesList();

        $aTabs = [
            [
                "DIV" => "edit_all",
                "TAB" => "Общие настройки",
                "OPTIONS" => [
//                    [
//                        'STRATEGY', // Ключ
//                        'Пользователь по умолчанию подписан на всё', // Название поля
//                        'Y', // По умолчанию
//                        [
//                            'checkbox',
//                        ]
//                    ],
                    [
                        'note' => 'По умолчанию пользователь подписан на всё. Он может отписаться в от типов событий.'
                    ],
                    [
                        'note' => 'Не забывать при добавлении новых типов событий - обновлять тут настройки!'
                    ],
                    [
                        'note' => 'Показываются только типы событий в которых есть активные почтовые шаблоны.'
                    ],
                    [
                        'TYPE_SYSTEM',
                        'Выбрать системные уведомления',
                        '',
                        [
                            'multiselectbox',
                            $listEmailTypeEvents
                        ]
                    ],
                    [
                        'TYPE_PERSONAL',
                        'Выбрать персональные скидки',
                        '',
                        [
                            'multiselectbox',
                            $listEmailTypeEvents
                        ],
                    ],
                    [
                        'TYPE_PARTNERS',
                        'Выбрать партнёрские уведомления',
                        '',
                        [
                            'multiselectbox',
                            $listEmailTypeEvents
                        ],
                    ],
                    [
                        'TYPE_SENDER',
                        'Добавлять в ЧС модуля sender при отписке пользователя массовых рассылок',
                        'Y',
                        [
                            'checkbox'
                        ],
                    ]
                ]
            ]
        ];

        // Сохраняем настройки
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && strlen($_REQUEST['save']) > 0 && $AUTH_RIGHT == "W" && check_bitrix_sessid()) {
            foreach ($aTabs as $aTab) {
                \__AdmSettingsSaveOptions($this->module_id, $aTab['OPTIONS']);
            }
            LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID . '&mid_menu=1&mid=' . urlencode($this->module_id) .
                    '&tabControl_active_tab=' . urlencode($_REQUEST['tabControl_active_tab']) . '&sid=' . urlencode(SITE_ID));
        }

        // Показываем форму
        $tabControl = new \CAdminTabControl('tabControl', $aTabs);
        ?><form method='post' action='' name='bootstrap'>
            <?
            $tabControl->Begin();
            foreach ($aTabs as $aTab) {
                $tabControl->BeginNextTab();
                \__AdmSettingsDrawList($this->module_id, $aTab['OPTIONS']);
            }
            $tabControl->Buttons(array('btnApply' => false, 'btnCancel' => false, 'btnSaveAndAdd' => false));
            ?>&nbsp;&nbsp;<button class="adm-btn" 
                                type="button" 
                                onclick="checkEmail()"
                                title="Проверить email" />Проверить email</button><input value="<?
            global $USER;
            echo $USER->GetEmail();
            ?>" type="email" id="email" />
        <?= \bitrix_sessid_post(); ?>
        <script>
            function checkEmail() {
                let email = BX('email').value;
                BX.showWait();
                BX.ajax({
                    url: '<?= $APPLICATION->GetCurPageParam("", ['action', 'email', 'sessid']) ?>',
                    data: {email: email, sessid: BX.message('bitrix_sessid')},
                    method: 'POST',
        //dataType: 'json',
                    timeout: 60,
                    async: true,
                    processData: true,
                    scriptsRunFirst: true,
                    emulateOnload: true,
                    start: true,
                    cache: false,
                    onsuccess: function (data) {
                        console.log(data);
                        alert(data);
        //
        //                data = BX.parseJSON(data);
        //
        //                if (data.html) {
        //                    BX.adjust(BX('crimson_tab_' + tab + "_edit_table"), {html: data.html});
        //                }
                        BX.closeWait();

                    }, onfailure: e => {
                        BX.closeWait();
        //console.error(e);
                    }
                });
                return true;

            }
        </script>
        <? $tabControl->End(); ?>
        </form><?
    }

}

new \CrimsonUnsubscribeOptions();
