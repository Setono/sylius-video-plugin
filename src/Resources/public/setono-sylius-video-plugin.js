/*
 * Dependency-light controller for the admin product "Videos" tab.
 *
 * Sole responsibility: reveal only the kind-specific field (path/url/html) matching the selected
 * type, hiding and disabling the others. Ordering is handled by the plain `position` integer
 * field, so there is no JavaScript involved in reordering.
 *
 * It hooks into Sylius's own collection markup (`[data-form-collection="item"]`) so it needs no
 * custom form theme, works for rows added through the collection prototype (observed), and uses
 * no framework so it can ship as-is in Resources/public.
 */
(function () {
    'use strict';

    function wrapperOf(input) {
        return input.closest('.field') || input.parentNode;
    }

    function activeFieldOf(select) {
        var option = select.options[select.selectedIndex];

        return option ? option.getAttribute('data-video-field-target') : null;
    }

    function isVideoItem(item) {
        return !!item.querySelector('[data-video-type-select]');
    }

    function toggle(item) {
        var select = item.querySelector('[data-video-type-select]');
        if (!select) {
            return;
        }
        var activeField = activeFieldOf(select);
        item.querySelectorAll('[data-video-field]').forEach(function (input) {
            var matches = input.getAttribute('data-video-field') === activeField;
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

    document.addEventListener('change', function (event) {
        var target = event.target;
        if (target && target.matches && target.matches('[data-video-type-select]')) {
            var item = target.closest('[data-form-collection="item"]');
            if (item) {
                toggle(item);
            }
        }
    });

    function boot() {
        initItems(document);

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
