humhub.module('mail.conversationDummy', function(module, require, $) {
    const selectors = {
        dummy: '#mail-conversation-dummy',
        conversation: '#mail-conversation-root',
        inboxEntries: '#inbox .entry'
    };

    const hasIdParam = function () {
        const queryParams = new URLSearchParams(window.location.search);
        return queryParams.has('id');
    }

    const isEmptyEntryList = function () {
        return !$(selectors.inboxEntries).length;
    }

    const show = function () {
        if (!isEmptyEntryList()) {
            $(selectors.dummy).show();
        }
        $(selectors.conversation).hide();
    }

    const hide = function () {
        $(selectors.dummy).hide();
        $(selectors.conversation).show();
    }

    const hideAll = function () {
        $(selectors.dummy).hide();
        $(selectors.conversation).hide();
    }

    const init = function() {
        if (hasIdParam()) {
            hide();
            return;
        }
        show();
    }

    module.export({
        initOnPjaxLoad: true,
        init: init,
        show: show,
        hide: hide,
        hideAll: hideAll
    });
});
