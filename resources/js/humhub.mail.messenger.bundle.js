humhub.module('mail.ConversationView', function (module, require, $) {

    var Widget = require('ui.widget').Widget;
    var loader = require('ui.loader');
    var client = require('client');
    var additions = require('ui.additions');
    var object = require('util.object');
    var mail = require('mail.notification');
    var view = require('ui.view');

    var ConversationView = Widget.extend();

    ConversationView.prototype.init = function () {
        additions.observe(this.$);

        var that = this;
        window.onresize = function (evt) {
            that.updateSize(true);
        };

        if (!this.getActiveMessageId()) {
            this.setActiveMessageId(Widget.instance('#inbox').getFirstMessageId());
        }

        this.reload();

        this.$.on('mouseenter', '.mail-conversation-entry', function () {
            $(this).find('.conversation-menu').show();
        }).on('mouseleave', '.mail-conversation-entry', function () {
            $(this).find('.conversation-menu').hide();
        });
    };

    ConversationView.prototype.loader = function (load) {
        if (load !== false) {
            loader.set(this.$);
        } else {
            loader.reset(this.$);
        }
    };

    ConversationView.prototype.markSeen = function (id) {
        client.post(this.options.markSeenUrl, {data: {id: id}}).then(function (response) {
            if (object.isDefined(response.messageCount)) {
                mail.setMailMessageCount(response.messageCount);
            }
        }).catch(function (e) {
            module.log.error(e);
        });
    };

    ConversationView.prototype.loadUpdate = function () {
        var $lastEntry = this.$.find('.mail-conversation-entry:not(.own):last');
        var lastEntryId = $lastEntry.data('entry-id');
        var data = {id: this.getActiveMessageId(), from: lastEntryId};

        var that = this;
        client.get(this.options.loadUpdateUrl, {data: data}).then(function (response) {
            if (response.html) {
                $(response.html).each(function () {
                    that.appendEntry($(this));
                });
            }
        })
    };

    ConversationView.prototype.reply = function (evt) {
        var that = this;
        client.submit(evt).then(function (response) {
            if (response.success) {
                that.appendEntry(response.content).then(function() {
                    that.$.find(".time").timeago(); // somehow this is not triggered after reply
                    var richtext = that.getReplyRichtext();
                    richtext.$.trigger('clear');
                    that.scrollToBottom();
                    if(!view.isSmall()) { // prevent autofocus on mobile
                        that.focus();
                    }
                    Widget.instance('#inbox').updateEntries([that.getActiveMessageId()]);
                    that.setLivePollInterval();
                });
            } else {
                module.log.error(response, true);
            }
        }).catch(function (e) {
            module.log.error(e, true);
        }).finally(function (e) {
            loader.reset($('.reply-button'));
            evt.finish();
        });
    };

    ConversationView.prototype.setLivePollInterval = function () {
        require('live').setDelay(5);
    };

    ConversationView.prototype.getReplyRichtext = function () {
        return Widget.instance(this.$.find('.ProsemirrorEditor'));
    };


    ConversationView.prototype.focus = function (evt) {
        this.getReplyRichtext().focus();
    };

    ConversationView.prototype.canLoadMore = function () {
        return !this.options.isLast;
    };

    ConversationView.prototype.reload = function () {
        if (this.getActiveMessageId()) {
            this.loadMessage(this.getActiveMessageId());
        }
    };

    ConversationView.prototype.addUser = function (evt) {
        var that = this;

        client.submit(evt).then(function (response) {
            if (response.result) {
                that.$.find('#mail-conversation-header').html(response.result);
            } else if (response.error) {
                module.log.error(response, true);
            }
        }).catch(function (e) {
            module.log.error(e, true);
        });
    };

    ConversationView.prototype.appendEntry = function (html) {
        var that = this;
        var $html = $(html);

        if (that.$.find('[data-entry-id="' + $html.data('entryId') + '"]').length) {
            return Promise.resolve();
        }

        // Filter out all script/links and text nodes
        var $elements = $html.not('script, link').filter(function () {
            return this.nodeType === 1; // filter out text nodes
        });

        // We use opacity because some additions require the actual size of the elements.
        $elements.css('opacity', 0);

        // call insert callback
        this.getListNode().append($html);

        return new Promise(function(resolve, reject) {
            $elements.css('opacity', 1).fadeIn('fast', function () {
                that.onUpdate();
                setTimeout(function() {that.scrollToBottom()}, 100);
                resolve();
            });
        })
    };

    ConversationView.prototype.loadMessage = function (evt) {
        var messageId = object.isNumber(evt) ? evt : evt.$trigger.data('message-id');
        var that = this;
        this.loader();
        client.get(this.options.loadMessageUrl, {data: {id: messageId}}).then(function (response) {
            that.setActiveMessageId(messageId);
            that.options.isLast = false;

            var inbox = Widget.instance('#inbox');
            inbox.updateActiveItem();
            inbox.hide();

            // Replace history state only if triggered by message preview item
            if (evt.$trigger && history && history.replaceState) {
                var url = evt.$trigger.data('action-url');
                if (url) {
                    history.replaceState(null, null, url);
                }
            }

            that.$.css('visibility', 'hidden');
            return that.updateContent(response.html);
        }).then(function () {
            return that.initScroll();
        }).catch(function (e) {
            module.log.error(e, true);
        }).finally(function () {
            that.scrollToBottom()
            that.loader(false);
            that.$.css('visibility', 'visible');
            that.initReplyRichText();

            const $chatTitleWrap = $('.chat-title-wrap')
            const $textTitle = $chatTitleWrap.children('span')
            that.makeScrollable($chatTitleWrap, $textTitle)

            const $occupationWrap = $('.chat-occupation-wrap')
            const $occupationText = $occupationWrap.children('.rocketcore-user-occupation')
            $occupationWrap.on('mouseenter', function() {
                that.makeScrollable($occupationWrap, $occupationText, false)
            })
            $occupationWrap.on('mouseleave').on('mouseleave', function() {
                $occupationWrap.animate({scrollLeft: 0}, 3500)
            })
        });
    };

    ConversationView.prototype.makeScrollable = function ($wrap, $textNode, looped = true, scrollDelay = 1500, scrollDuration = 3500) {
        if ($wrap.innerWidth() < $textNode.innerWidth()) {
            const offsetLeft = $wrap.offset().left

            const scrollLoopTitle = () => {
                setTimeout(() => {
                    $wrap.animate({scrollLeft: offsetLeft}, scrollDuration, () => {
                        setTimeout(() => $wrap.animate({scrollLeft: 0}, scrollDuration, function() {
                            if (looped) {
                                scrollLoopTitle()
                            }
                        }), scrollDelay)
                    })
                }, scrollDelay)
            }
            scrollLoopTitle()
        }
    }

    ConversationView.prototype.initReplyRichText = function () {
        var that = this;

        if(window.ResizeObserver) {
            var resizeObserver = new ResizeObserver(function(entries) {
                that.updateSize(that.isScrolledToBottom(100));
            });

            resizeObserver.observe(that.getReplyRichtext().$[0]);
        }

        that.focus();

    };

    ConversationView.prototype.isScrolledToBottom = function (tolerance) {
        var $list = this.getListNode();

        if(!$list.length) {
            return false;
        }

        tolerance = tolerance || 0;
        var list = this.getListNode()[0];
        return list.scrollHeight - list.offsetHeight - list.scrollTop <= tolerance;
    };

    ConversationView.prototype.initScroll = function () {
        if (window.IntersectionObserver) {
            var $entryList = this.$.find('.conversation-entry-list');
            var $streamEnd = $('<div class="conversation-stream-end"></div>');
            $entryList.prepend($streamEnd);

            var that = this;
            var observer = new IntersectionObserver(function (entries) {
                if (that.preventScrollLoading()) {
                    return;
                }

                if (entries.length && entries[0].isIntersecting) {
                    loader.prepend($entryList);
                    that.loadMore().finally(function () {
                        loader.reset($entryList);
                    });
                }

            }, {root: $entryList[0], rootMargin: "50px"});

            // Assure the conversation list is scrollable by loading more entries until overflow
            return this.assureScroll().then(function () {
                observer.observe($streamEnd[0]);
                if(view.isLarge()) {
                    that.getListNode().niceScroll({
                        cursorwidth: "7",
                        cursorborder: "",
                        cursorcolor: "#555",
                        cursoropacitymax: "0.2",
                        nativeparentscrolling: false,
                        railpadding: {top: 0, right: 0, left: 0, bottom: 0}
                    });
                }
            });
        }
    };

    ConversationView.prototype.loadMore = function () {
        var that = this;

        var data = {
            id: this.getActiveMessageId(),
            from: this.$.find('.mail-conversation-entry:first').data('entryId')
        };

        return client.get(this.options.loadMoreUrl, {data: data}).then(function (response) {
            if (response.result) {
                var $result = $(response.result).hide();
                that.$.find('.conversation-entry-list').find('.conversation-stream-end').after($result);
                $result.fadeIn();
            }

            that.options.isLast = !response.result || response.isLast;
        }).catch(function (err) {
            module.log.error(err, true);
        });
    };

    ConversationView.prototype.preventScrollLoading = function () {
        return this.scrollLock || !this.canLoadMore();
    };

    ConversationView.prototype.canLoadMore = function () {
        return !this.options.isLast;
    };

    ConversationView.prototype.assureScroll = function () {
        var that = this;
        var $entryList = this.$.find('.conversation-entry-list');
        if ($entryList[0].offsetHeight >= $entryList[0].scrollHeight && this.canLoadMore()) {
            return this.loadMore().then(function () {
                return that.assureScroll();
            }).catch(function () {
                return Promise.resolve();
            })
        }

        return that.scrollToBottom();
    };

    ConversationView.prototype.updateContent = function (html) {
        var that = this;
        return new Promise(function (resolve) {
            that.$.html(html);
            resolve();
        });
    };


    ConversationView.prototype.getActiveMessageId = function () {
        return this.options.messageId;
    };

    ConversationView.prototype.setActiveMessageId = function (id) {
        this.options.messageId = id;
    };

    ConversationView.prototype.scrollToBottom = function () {
        var that = this;

        return new Promise(function (resolve) {
            setTimeout(function() {
                that.$.imagesLoaded(function() {
                    var $list = that.getListNode();
                    if(!$list.length) {
                        return;
                    }

                    that.updateSize(false).then(function () {
                        $list.scrollTop($list[0].scrollHeight)
                        resolve()
                    });
                })
            });
        });
    };

    ConversationView.prototype.updateSize = function (scrollToButtom) {
        var that = this;
        return new Promise(function (resolve) {
            setTimeout(function () {
                var $entryContainer = that.$.find('.conversation-entry-list');

                if (!$entryContainer.length) {
                    return;
                }

                var formHeight = that.getReplyRichtext().$.innerHeight();
                $entryContainer.css('margin-bottom' , formHeight + 5 + 'px');

                var offsetTop = that.$.find('.conversation-entry-list').offset().top;
                var max_height = (window.innerHeight - offsetTop - formHeight - (view.isSmall() ? 20 : 30)) + 'px';
                $entryContainer.css('height', max_height);
                $entryContainer.css('max-height', max_height);

                if(scrollToButtom !== false) {
                    that.scrollToBottom();
                }
                resolve();
            }, 100);
        })

    };

    ConversationView.prototype.getListNode = function () {
        return this.$.find('.conversation-entry-list');
    };

    ConversationView.prototype.onUpdate = function () {
        if(view.isLarge()) {
            this.getListNode().getNiceScroll().resize();
        }
    };

    module.export = ConversationView;
});
humhub.module('mail.ConversationEntry', function (module, require, $) {

    var Widget = require('ui.widget').Widget;

    var ConversationEntry = Widget.extend();

    ConversationEntry.prototype.replace = function (dom) {
        var that = this;
        var $content = $(dom).hide();
        this.$.fadeOut(function () {
            $(this).replaceWith($content);
            that.$ = $content;
            that.$.fadeIn('slow');
        });
    };

    ConversationEntry.prototype.remove = function () {
        this.$.fadeToggle('slow', function () {
            $(this).remove();
        });
    };

    module.export = ConversationEntry;
});
humhub.module('mail.inbox', function (module, require, $) {

    var Widget = require('ui.widget').Widget;
    var Filter = require('ui.filter').Filter;
    var view = require('ui.view');
    var loader = require('ui.loader');
    var client = require('client');

    var ConversationFilter = Filter.extend();

    ConversationFilter.prototype.triggerChange = function() {
        this.super('triggerChange');
        this.updateFilterCount();
    };

    ConversationFilter.prototype.updateFilterCount = function () {
        var count = this.getActiveFilterCount();

        var $filterToggle = this.$.find('#conversation-filter-link');
        var $filterCount = $filterToggle.find('.filterCount');

        if(count) {
            if(!$filterCount.length) {
                $filterCount = $('<small class="filterCount"></small>').insertBefore($filterToggle.find('.caret'));
            }
            $filterCount.html(' <b>('+count+')</b> ');
        } else if($filterCount.length) {
            $filterCount.remove();
        }
    };

    var ConversationList = Widget.extend();

    ConversationList.prototype.init = function () {
        this.filter = Widget.instance('#mail-filter-root');

        this.initScroll();

        var that = this;
        this.filter.off('afterChange.inbox').on('afterChange.inbox', function () {
            that.reload().then(function() {
                that.updateActiveItem();
            });
        });

        if(view.isLarge()) {
            this.$.niceScroll({
                cursorwidth: "7",
                cursorborder: "",
                cursorcolor: "#555",
                cursoropacitymax: "0.2",
                nativeparentscrolling: false,
                railpadding: {top: 0, right: 3, left: 0, bottom: 0}
            });
        }

        this.$.on('click', '.entry', function() {
            that.$.find('.entry').removeClass('selected');
            $(this).addClass('selected');
        })

    };

    ConversationList.prototype.updateEntries = function(ids) {
        var that = this;

        if(!ids.length) {
            return;
        }

        client.get(this.options.updateEntriesUrl, {data: {ids: ids}}).then(function(response) {
            if(!response.result)  {
                return;
            }

            $.each(response.result, function(id, html) {
                var $entry = that.getEntry(id);
                if(!$entry.length) {
                    $(html).prependTo(that.$) ;
                } else {
                   $entry.replaceWith(html);
                }
            });

            that.updateActiveItem();
        }).catch(function(e) {
            module.log.error(e);
        });
    };

    ConversationList.prototype.getEntry = function(id) {
        return this.$.find('[data-message-preview="'+id+'"]');
    };

    ConversationList.prototype.initScroll = function() {
        if (window.IntersectionObserver) {

            var $streamEnd = $('<div class="inbox-stream-end"></div>');
            this.$.append($streamEnd);

            var that = this;
            var observer = new IntersectionObserver(function (entries) {
                if (that.preventScrollLoading()) {
                    return;
                }

                if (entries.length && entries[0].isIntersecting) {
                    loader.append(that.$);
                    that.loadMore().finally(function() {
                        loader.reset(that.$);
                    });
                }

            }, {root: this.$[0], rootMargin: "50px"});

            // Assure the conversation list is scrollable by loading more entries until overflow
            this.assureScroll().then(function() {
                observer.observe($streamEnd[0]);
            });
        }
    };

    ConversationList.prototype.assureScroll = function () {
        var that = this;

        if(this.$[0].offsetHeight >= this.$[0].scrollHeight && this.canLoadMore()) {
            return this.loadMore().then(function() {
                return that.assureScroll();
            }).catch(function () {
                return Promise.resolve();
            })
        }

        return Promise.resolve();
    };

    ConversationList.prototype.loadMore = function () {
        var that = this;
        return new Promise(function(resolve, reject) {
            var data = that.filter.getFilterMap();
            data.from = that.getLastMessageId();
            client.get(that.options.loadMoreUrl, {data: data}).then(function(response) {
                if(response.result) {
                    $(response.result).insertBefore('.inbox-stream-end');
                    that.$.find('.inbox-stream-end').append();
                }

                that.options.isLast = !response.result || response.isLast;
                that.updateActiveItem();

                resolve();
            }).catch(function(err) {
                module.log.error(err, true);
                reject();
            }).finally(function() {
                that.scrollLock = false;
            });
        });

    };

    ConversationList.prototype.preventScrollLoading = function () {
        return this.scrollLock || !this.canLoadMore();
    };

    ConversationList.prototype.canLoadMore = function () {
        return !this.options.isLast;
    };

    ConversationList.prototype.getReloadOptions = function () {
        return {data: this.filter.getFilterMap()};
    };

    ConversationList.prototype.updateActiveItem = function() {
        var instance = Widget.instance('#mail-conversation-root')
        if (!instance) {
            return
        }
        
        var activeMessageId = instance.getActiveMessageId();

        this.$.find('.entry').removeClass('selected');

        // Remove New badge from current selection
        this.$.find('.entry.selected').find('.new-message-badge').hide();

        // Set new selection
        this.$.find('.entry').removeClass('selected');
        var $selected = this.$.find('[data-message-preview="' + activeMessageId + '"]');

        if($selected.length) {
            $selected.removeClass('unread').addClass('selected')
            $selected.find('.new-message-badge').hide();
            $selected.find('.chat-count').hide();
        }
    };


    ConversationList.prototype.getFirstMessageId = function() {
        return this.$.find('.entry:first').data('messagePreview');
    };

    ConversationList.prototype.getLastMessageId = function() {
        return this.$.find('.entry:last').data('messagePreview');
    };

    ConversationList.prototype.hide = function() {
        return new Promise(function (resolve) {
            if(view.isSmall()) {
                $('.inbox-wrapper').slideUp(function() {
                    if($('#mail-conversation-root').length) {
                        Widget.instance('#mail-conversation-root').updateSize();
                    }
                    resolve();
                });
            }
            resolve();
        });
    };

    ConversationList.prototype.show = function() {
        return new Promise(function (resolve) {
            if(view.isSmall()) {
                $('.inbox-wrapper').slideDown(function() {
                    if($('#mail-conversation-root').length) {
                        Widget.instance('#mail-conversation-root').updateSize();
                    }

                    resolve();
                });
            }
            resolve();
        });
    };

    var toggleInbox = function() {
        if(view.isSmall()) {
            $('.inbox-wrapper').slideToggle(function() {
                Widget.instance('#mail-conversation-root').updateSize();
            });
        }
    };

    var setTagFilter = function (evt) {
        Widget.instance('#inbox').show().then(function() {
            $('#mail-filter-menu').collapse('show');
            Widget.instance('#inbox-tag-picker').setSelection([{
                id: evt.$trigger.data('tagId'),
                text: evt.$trigger.data('tagName'),
                image: evt.$trigger.data('tagImage'),
            }]);
        });
    };

    module.export({
        ConversationList: ConversationList,
        Filter: ConversationFilter,
        setTagFilter: setTagFilter,
        toggleInbox: toggleInbox
    });
});
humhub.module('mail.conversation', function (module, require, $) {

    var Widget = require('ui.widget').Widget;
    var modal = require('ui.modal');
    var client = require('client');
    var event = require('event');
    var mail = require('mail.notification');
    var user = require('user');

    var submitEditEntry = function (evt) {
        modal.submit(evt).then(function (response) {
            if (response.success) {
                var entry = getEntry(evt.$trigger.data('entry-id'));
                if (entry) {
                    setTimeout(function () {
                        entry.replace(response.content);
                    }, 300)
                }

                return;
            }

            module.log.error(null, true);
        }).catch(function (e) {
            module.log.error(e, true);
        });
    };

    var deleteEntry = function (evt) {
        var entry = getEntry(evt.$trigger.data('entry-id'));

        if (!entry) {
            module.log.error(null, true);
            return;
        }

        client.post(entry.options.deleteUrl).then(function (response) {
            modal.global.close();

            if (response.success) {
                setTimeout(function () {
                    entry.remove();
                }, 1000);
            }
        }).catch(function (e) {
            module.log.error(e, true);
        });
    };

    var getEntry = function (id) {
        return Widget.instance('.mail-conversation-entry[data-entry-id="' + id + '"]');
    };

    var getRootView = function () {
        return Widget.instance('#mail-conversation-root');
    };

    var init = function () {
        event.on('humhub:modules:mail:live:NewUserMessage', function (evt, events) {
            if(!$('#inbox').length) {
                return;
            }

            var root = getRootView();
            var updated = false;
            var updatedMessages = [];
            events.forEach(function (event) {
                var isOwn = event.data['user_guid'] == user.guid();
                updatedMessages.push(event.data.message_id);
                if (!updated && root && root.options.messageId == event.data.message_id) {
                    root.loadUpdate();
                    updated = true;
                    root.markSeen(event.data.message_id);
                } else if (!isOwn && root) {
                    var $entry = getOverViewEntry(event.data.message_id);
                    if(!$entry.is('.selected')) {
                        $entry.find('.new-message-badge').show();
                    }
                }
            });

            Widget.instance('#inbox').updateEntries(updatedMessages);
        }).on('humhub:modules:mail:live:UserMessageDeleted', function (evt, events, update) {
            if(!$('#inbox').length) {
                return;
            }

            events.forEach(function (event) {
                var entry = getEntry(event.data.entry_id);
                if (entry) {
                    entry.remove();
                }
                mail.setMailMessageCount(event.data.count);
            });
        });
    };

    var getOverViewEntry = function (id) {
        return $('#mail-conversation-overview').find('[data-message-preview="' + id + '"]');
    };

    var leave = function (evt) {
        client.post(evt).then(function (response) {
            if (response.redirect) {
                client.pjax.redirect(response.redirect);
            }
        }).catch(function (e) {
            module.log.error(e, true);
        });
    };

    module.export({
        init: init,
        leave: leave,
        submitEditEntry: submitEditEntry,
        deleteEntry: deleteEntry,
    });
});