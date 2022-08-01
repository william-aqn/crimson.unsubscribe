const SubscribeService = function (selector, extraData = false) {
    this.container = document.querySelector(selector);
    this.container.addEventListener('click', (e) => this.eventHandler(e));
    this.extraData = extraData;
};

SubscribeService.prototype = {
    eventHandler: function (e) {
        if (e.target.tagName === 'INPUT') {
            let unwantedEvents = '';
            this.container.querySelectorAll('input:not(:checked)').forEach((input) => {
                let separator = unwantedEvents ? ',' : '';
                unwantedEvents += separator + input.dataset.type;
            });
            console.log(unwantedEvents);
            this.updatePreferences(unwantedEvents);
        }
    },
    updatePreferences: function (new_data) {
        let data = new FormData();
        data.set('sessid', BX.bitrix_sessid());
        data.set('types', new_data ? new_data : ' ');
        if (this.extraData) {
            for (let key in this.extraData) {
                data.set(key, this.extraData[key]);
            }
        }
        fetch('/bitrix/services/main/ajax.php?c=crimson:unsubscribe.personal&action=unsubscribe&mode=ajax', {method: 'POST', body: data}).then((res) => res.json()).then((json) => {
            if (json.data && json.data.msg === 'ok') {
                alert('Ok');
            } else {
                alert('Failed to save settings');
            }
        });
    }
};