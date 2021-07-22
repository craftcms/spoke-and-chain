window.modal = function() {
    return {
        bodyId: 'modal-body',
        contentLoaded: false,
        focusableElements: [],
        modalType: 'centered', // modal type 'centered' or 'slideout'
        show: false,
        showWrapper: false,
        _nt: null,
        _focusableElementSelector: 'button, [href], input:not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])',

        init: function($nextTick) {
            var _this = this;
            this._nt = $nextTick;

            htmx.on('htmx:beforeSwap', function(event) {
                if (event.detail.target.getAttribute('id') == this.bodyId) {
                    this.contentLoaded = false;
                    this.focusableElements = [];
                }
            }.bind(this));

            htmx.on('htmx:afterSwap', function(event) {
                if (event.detail.target.getAttribute('id') == this.bodyId) {
                    this.contentLoaded = true;
                    this.refreshFocusableElements();
                }
            }.bind(this));

            var dc = document;
            document.addEventListener('keydown', function(e) {
                var isTabPressed = e.key === 'Tab' || e.keyCode === 9;

                if (!isTabPressed || !_this.show || !_this.contentLoaded) {
                    return;
                }

                var ffe = _this.getFirstFocusableElement();
                var lfe = _this.getLastFocusableElement();

                if (e.shiftKey) {
                    if (document.activeElement === ffe && lfe) {
                        lfe.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lfe && ffe) {
                        ffe.focus();
                        e.preventDefault();
                    }
                }
            });
        },

        openModal: function(type = 'slideout') {
            this.show = true
            this.showWrapper = true

            this.modalType = type

            this._nt(() => {
                document.querySelector('#modal').focus({ preventScroll: true });
            })
        },

        closeModal: function($nextTick) {
            this.show = false
            this.contentLoaded = false
            setTimeout(function() {
                this.showWrapper = false
            }.bind(this), 500)

            let previousFocusEl = window.previousFocus ? document.querySelector(window.previousFocus) : false;
            if (previousFocusEl) {
                this._nt(() => {
                    previousFocusEl.focus();
                });
                window.previousFocus = null;
            }
        },

        handleEscape() {
            if (this.show == true) {
                this.closeModal();
            }
        },

        refreshFocusableElements() {
            var modal = document.querySelector('#' + this.bodyId);
            if (!modal) {
                this.focusableElements = [];
                return;
            }

            this.focusableElements = modal.querySelectorAll(this._focusableElementSelector);
        },

        getFirstFocusableElement() {
            if (!this.focusableElements.length) {
                return null;
            }

            return this.focusableElements[0];
        },

        getLastFocusableElement() {
            if (!this.focusableElements.length) {
                return null;
            }

            return this.focusableElements[this.focusableElements.length - 1];
        },

        modalEffects: {
            ['x-transition:enter']() {
                if (this.modalType == 'slideout') {
                    return 'transform transition ease-in-out duration-500 sm:duration-700';
                }
                return 'transition ease-out duration-300';
            },
            ['x-transition:enter-start']() {
                if (this.modalType == 'slideout') {
                    return 'translate-x-full';
                }
                return 'opacity-0';
            },
            ['x-transition:enter-end']() {
                if (this.modalType == 'slideout') {
                    return 'translate-x-0';
                }
                return 'opacity-100';
            },
            ['x-transition:leave']() {
                if (this.modalType == 'slideout') {
                    return 'transform transition ease-in-out duration-500 sm:duration-700';
                }
                return 'transition ease-out duration-300';
            },
            ['x-transition:leave-start']() {
                if (this.modalType == 'slideout') {
                    return 'translate-x-0';
                }
                return 'opacity-100';
            },
            ['x-transition:leave-end']() {
                if (this.modalType == 'slideout') {
                    return 'translate-x-full';
                }
                return 'opacity-0';
            },
        }
    }
};

window.modalButton = function(handle = null, data = {}, type = 'slideout') {
    return {
        open: function($dispatch, previousFocusSelector) {
            if (handle) {
                htmx.find('#modal-handle').value = handle;
                htmx.find('#modal-data').value = JSON.stringify(data);
                htmx.trigger(htmx.find('#modal-body'), 'refresh');
                $dispatch('openmodal', type);
                if (previousFocusSelector) {
                    window.previousFocus = previousFocusSelector;
                }
            }
        }
    }
};

window.closeModal = function() {
    window.dispatchEvent(new CustomEvent('closemodal'));
};