/*
 * Dependency-light controller for the admin product "Videos" tab.
 *
 * Sole responsibility: reveal only the kind-specific field(s) matching the selected type, hiding
 * and disabling the others. Ordering is handled by the plain `position` integer field, so there
 * is no JavaScript involved in reordering.
 *
 * Sylius admin upgrades the `type` <select> into a Semantic UI dropdown, which emits its change
 * through jQuery's trigger() rather than as a native DOM event; a native `change` listener never
 * sees it. We therefore also bind through jQuery when it is present (it always is in the Sylius
 * admin), while keeping the native listener so the plugin still works with a plain <select>.
 *
 * It hooks into Sylius's own collection markup (`[data-form-collection="item"]`) so it needs no
 * custom form theme, works for rows added through the collection prototype (observed), and uses
 * no framework of its own.
 */
(function () {
    'use strict';

    function wrapperOf(input) {
        return input.closest('.field') || input.parentNode;
    }

    function isVideoItem(item) {
        return !!item.querySelector('[data-video-type-select]');
    }

    function toggle(item) {
        var select = item.querySelector('[data-video-type-select]');
        if (!select) {
            return;
        }
        var activeType = select.value;
        // Each kind's field(s) carry `data-video-fields="<type>"`; reveal the selected kind's and
        // hide (and disable, so it is not submitted) the rest.
        item.querySelectorAll('[data-video-fields]').forEach(function (input) {
            var matches = input.getAttribute('data-video-fields') === activeType;
            wrapperOf(input).style.display = matches ? '' : 'none';
            input.disabled = !matches;
        });
    }

    function initItems(root) {
        root = root || document;
        // A node added through the collection prototype IS the item itself, so check it directly
        // in addition to any descendant items (e.g. when initialising the whole document).
        if (root.matches && root.matches('[data-form-collection="item"]') && isVideoItem(root)) {
            toggle(root);
        }
        if (root.querySelectorAll) {
            root.querySelectorAll('[data-form-collection="item"]').forEach(function (item) {
                if (isVideoItem(item)) {
                    toggle(item);
                }
            });
        }
    }

    function handleTypeChange(select) {
        var item = select && select.closest ? select.closest('[data-form-collection="item"]') : null;
        if (item) {
            toggle(item);
        }
    }

    // Native change — covers a plain <select> and works without jQuery.
    document.addEventListener('change', function (event) {
        var target = event.target;
        if (target && target.matches && target.matches('[data-video-type-select]')) {
            handleTypeChange(target);
        }
    });

    function boot() {
        initItems(document);

        // Semantic UI dropdown changes only surface through jQuery (see file header).
        if (window.jQuery) {
            window.jQuery(document).on('change', '[data-video-type-select]', function () {
                handleTypeChange(this);
            });
        }

        document.querySelectorAll('[data-form-collection="list"]').forEach(function (list) {
            if (list.__setonoVideoBound) {
                return;
            }
            list.__setonoVideoBound = true;

            // Newly added prototype rows need their kind-specific field revealed too.
            new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) {
                            initItems(node);
                        }
                    });
                });
            }).observe(list, { childList: true });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
