/*
 * Dependency-light controller for the admin product "Videos" tab.
 *
 * Sole responsibility: reveal only the type-specific field(s) matching the selected type, hiding
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
        // Each type's field(s) carry `data-video-fields="<type>"`; reveal the selected type's and
        // hide (and disable, so it is not submitted) the rest.
        item.querySelectorAll('[data-video-fields]').forEach(function (input) {
            var matches = input.getAttribute('data-video-fields') === activeType;
            // A hidden input has nothing to show; hiding its wrapper would hide its siblings too.
            if (input.type !== 'hidden') {
                wrapperOf(input).style.display = matches ? '' : 'none';
            }
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

            // Newly added prototype rows need their type-specific field revealed too.
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

/*
 * Direct upload to Cloudflare Stream for the "Cloudflare Stream" video type.
 *
 * The file never passes through the shop: when a file is picked in an input carrying
 * `data-cloudflare-stream-upload` (its value is the admin endpoint that creates the upload), the
 * controller asks that endpoint for a one-time tus upload URL and the future video uid, PATCHes the
 * file to Cloudflare in chunks (tus protocol, resumable offsets), and finally writes the uid into
 * the row's hidden `[data-cloudflare-stream-uid]` input — the only thing the product form submits.
 * Saving is blocked while an upload is running.
 */
(function () {
    'use strict';

    var DEFAULT_CHUNK_SIZE = 50 * 1024 * 1024;

    function messages(input) {
        try {
            return JSON.parse(input.getAttribute('data-cloudflare-stream-messages') || '{}') || {};
        } catch (e) {
            return {};
        }
    }

    function fieldOf(input) {
        return input.closest('.field') || input.parentNode;
    }

    function rowOf(input) {
        return input.closest('[data-form-collection="item"]') || fieldOf(input);
    }

    function uidInputOf(input) {
        return rowOf(input).querySelector('[data-cloudflare-stream-uid]');
    }

    function statusOf(input) {
        var field = fieldOf(input);
        var status = field.querySelector('[data-cloudflare-stream-status]');
        if (!status) {
            status = document.createElement('div');
            status.setAttribute('data-cloudflare-stream-status', '');
            status.className = 'ui small progress';
            status.style.marginTop = '0.5em';
            status.innerHTML = '<div class="bar" style="transition: width 0.2s; min-width: 0;"></div><div class="label"></div>';
            field.appendChild(status);
        }
        return status;
    }

    function setStatus(input, text, percent, state) {
        var status = statusOf(input);
        status.className = 'ui small progress' + (state ? ' ' + state : '');
        status.querySelector('.bar').style.width = Math.max(0, Math.min(100, percent)) + '%';
        status.querySelector('.label').textContent = text;
    }

    function submitButtonsOf(input) {
        var form = input.form;
        if (!form) {
            return [];
        }
        var buttons = Array.prototype.slice.call(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        if (form.id) {
            buttons = buttons.concat(Array.prototype.slice.call(document.querySelectorAll('button[form="' + form.id + '"]')));
        }
        return buttons;
    }

    function lockForm(input, locked) {
        submitButtonsOf(input).forEach(function (button) {
            button.disabled = locked;
        });
    }

    var uploadsInFlight = 0;

    window.addEventListener('beforeunload', function (event) {
        if (uploadsInFlight > 0) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    function createUpload(endpoint, file) {
        return fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ name: file.name, size: file.size })
        }).then(function (response) {
            return response.json().catch(function () {
                return {};
            }).then(function (data) {
                if (!response.ok) {
                    throw new Error(data.error || ('HTTP ' + response.status));
                }
                if (!data.uploadUrl || !data.uid) {
                    throw new Error('The upload endpoint returned no upload URL.');
                }
                return data;
            });
        });
    }

    function patchChunk(uploadUrl, file, offset, chunkSize, onProgress) {
        return new Promise(function (resolve, reject) {
            var end = Math.min(offset + chunkSize, file.size);
            var request = new XMLHttpRequest();
            request.open('PATCH', uploadUrl, true);
            request.setRequestHeader('Tus-Resumable', '1.0.0');
            request.setRequestHeader('Upload-Offset', String(offset));
            request.setRequestHeader('Content-Type', 'application/offset+octet-stream');
            request.upload.onprogress = function (event) {
                onProgress(offset + event.loaded);
            };
            request.onload = function () {
                if (request.status >= 200 && request.status < 300) {
                    var next = parseInt(request.getResponseHeader('Upload-Offset'), 10);
                    resolve(isNaN(next) ? end : next);
                } else {
                    reject(new Error('Cloudflare answered HTTP ' + request.status + ' while uploading.'));
                }
            };
            request.onerror = function () {
                reject(new Error('The connection to Cloudflare was lost while uploading.'));
            };
            request.send(file.slice(offset, end));
        });
    }

    function uploadChunks(uploadUrl, file, chunkSize, onProgress) {
        var offset = 0;
        function next() {
            if (offset >= file.size) {
                return Promise.resolve();
            }
            return patchChunk(uploadUrl, file, offset, chunkSize, onProgress).then(function (newOffset) {
                offset = newOffset;
                return next();
            });
        }
        return next();
    }

    function upload(input, file) {
        var texts = messages(input);
        var endpoint = input.getAttribute('data-cloudflare-stream-upload');
        var chunkSize = parseInt(input.getAttribute('data-cloudflare-stream-chunk-size'), 10) || DEFAULT_CHUNK_SIZE;
        var uidInput = uidInputOf(input);

        uploadsInFlight++;
        lockForm(input, true);
        setStatus(input, texts.preparing || 'Preparing upload…', 0, 'active');

        return createUpload(endpoint, file).then(function (data) {
            return uploadChunks(data.uploadUrl, file, chunkSize, function (uploaded) {
                var percent = file.size > 0 ? Math.round(uploaded / file.size * 100) : 100;
                setStatus(input, (texts.uploading || 'Uploading…') + ' ' + percent + '%', percent, 'active');
            }).then(function () {
                if (uidInput) {
                    uidInput.value = data.uid;
                }
                // The file must not travel with the form as well; the uid is what is saved.
                input.value = '';
                setStatus(input, (texts.uploaded || 'Uploaded.') + ' (' + file.name + ')', 100, 'success');
            });
        }).catch(function (error) {
            // Drop the file so a later save does not post it to the shop after all; picking it
            // again retries the upload.
            input.value = '';
            setStatus(input, (texts.failed || 'Upload failed:') + ' ' + error.message, 0, 'error');
        }).then(function () {
            uploadsInFlight--;
            lockForm(input, false);
        });
    }

    document.addEventListener('change', function (event) {
        var target = event.target;
        if (target && target.matches && target.matches('[data-cloudflare-stream-upload]') && target.files && target.files[0]) {
            upload(target, target.files[0]);
        }
    });
})();
