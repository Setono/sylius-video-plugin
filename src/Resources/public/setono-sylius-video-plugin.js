/*
 * Dependency-light controller for the admin product "Videos" tab.
 *
 * Responsibilities:
 *  - reveal only the kind-specific field (path/url/html) matching the selected type, hiding and
 *    disabling the others (works with or without JavaScript on the server side);
 *  - let editors reorder rows by dragging a handle, writing the new order into the hidden
 *    position inputs (also renumbered on submit as a safety net);
 *  - keep working for rows added through Sylius's data-form-collection prototype (observed).
 *
 * It hooks into Sylius's own collection markup (`[data-form-collection="item"]`) so it needs no
 * custom form theme, and uses no framework so it can ship as-is in Resources/public.
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

    function addDragHandle(item) {
        if (item.querySelector('[data-video-drag-handle]')) {
            return;
        }
        var handle = document.createElement('a');
        handle.setAttribute('href', '#');
        handle.setAttribute('data-video-drag-handle', '');
        handle.setAttribute('draggable', 'true');
        handle.className = 'ui basic icon button';
        handle.style.cssText = 'cursor: move; margin-bottom: 1em;';
        handle.innerHTML = '<i class="bars icon"></i>';
        handle.addEventListener('click', function (event) {
            event.preventDefault();
        });
        item.insertBefore(handle, item.firstChild);
    }

    function prepareItem(item) {
        if (!isVideoItem(item)) {
            return;
        }
        toggle(item);
        addDragHandle(item);
    }

    function initItems(root) {
        root = root || document;
        // A node added through the collection prototype IS the item itself, so check it directly
        // in addition to any descendant items (e.g. when initialising the whole document).
        if (root.matches && root.matches('[data-form-collection="item"]')) {
            prepareItem(root);
        }
        if (root.querySelectorAll) {
            root.querySelectorAll('[data-form-collection="item"]').forEach(prepareItem);
        }
    }

    function renumber() {
        document.querySelectorAll('[data-form-collection="list"]').forEach(function (list) {
            var index = 0;
            list.querySelectorAll(':scope > [data-form-collection="item"]').forEach(function (item) {
                if (!isVideoItem(item)) {
                    return;
                }
                var position = item.querySelector('[data-video-position]');
                if (position) {
                    position.value = index;
                }
                index += 1;
            });
        });
    }

    function itemOf(node) {
        return node && node.closest ? node.closest('[data-form-collection="item"]') : null;
    }

    function wireDragAndDrop(list) {
        var dragged = null;

        list.addEventListener('dragstart', function (event) {
            var handle = event.target.closest ? event.target.closest('[data-video-drag-handle]') : null;
            if (!handle) {
                return;
            }
            dragged = itemOf(handle);
            if (dragged && event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', '');
            }
        });

        list.addEventListener('dragover', function (event) {
            if (!dragged) {
                return;
            }
            var target = itemOf(event.target);
            if (!target || target === dragged || target.parentNode !== list) {
                return;
            }
            event.preventDefault();
            var rect = target.getBoundingClientRect();
            var after = (event.clientY - rect.top) > (rect.height / 2);
            list.insertBefore(dragged, after ? target.nextSibling : target);
        });

        list.addEventListener('drop', function (event) {
            if (!dragged) {
                return;
            }
            event.preventDefault();
            renumber();
            dragged = null;
        });
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

            wireDragAndDrop(list);

            new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) {
                            initItems(node);
                        }
                    });
                });
            }).observe(list, { childList: true });

            var form = list.closest('form');
            if (form && !form.__setonoVideoRenumber) {
                form.__setonoVideoRenumber = true;
                form.addEventListener('submit', renumber);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
