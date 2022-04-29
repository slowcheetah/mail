humhub.module('mail.unseen', function(module, require, $) {
    const client = require('client');
    const mailNotification = require('mail.notification');
    const Widget = require('ui.widget').Widget;
    const selectors = {
        container: '#mail-conversation-root',
        dropdown: '#mail-conversation-header ul.dropdown-menu',
        linkId: 'mail-mark-unseen',
        inbox: '#inbox'
    };

    const isNeedInit = function () {
        const isLastMessageMine = Widget.instance(selectors.container).isLastMessageMine();
        const hasUnseenButton = $(selectors.container).find(`#${selectors.linkId}`).length;
        return !hasUnseenButton && !isLastMessageMine;
    }

    const addUnseenButton = function () {
        const messageId = Widget.instance(selectors.container).getActiveMessageId();
        const $dropdown = $(selectors.dropdown);
        const $li = $('<li>').append(
            $('<a>', {
                'id': selectors.linkId,
                'data-action-click': 'mail.unseen.unseen',
                'data-action-url': module.config['unseenUrl'],
                'data-action-params': `{"id": ${messageId}}`
            }).text(module.text('Mark unseen'))
        );
        $dropdown.append($li);
    }

    const init = function() {
        if (!isNeedInit()) {
            return;
        }
        addUnseenButton();
    }

    const unseen = function ($evt) {
        const id = $evt.params.id;
        client.post($evt, {data: {id: id}}).then((response) => {
            Widget.instance(selectors.container).close();
            mailNotification.setMailMessageCount(response.messageCount);
            Widget.instance(selectors.inbox).reload();
        }).catch((err) => {
            module.log.error(err, true);
        });
    }

    module.export({
        init: init,
        unseen: unseen
    });
});
