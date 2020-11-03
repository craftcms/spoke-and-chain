window.modal = function() {
    return {
        action: null, // action string for the form
        buttons: [], // buttons to display format: {}
        callback: null, // buttons to display format: {}
        form: false, // whether or not to output the form
        header: null, // header text
        method: 'post',
        modalType: 'centered', // modal type 'centered' or 'slideout'
        redirect: null,
        show: false,
        showWrapper: false,
        url: null, // HTML content for the modal

        openModal: function(options) {
            console.log('openModal', options)
            this.show = true
            this.showWrapper = true

            this.action = options.action != undefined ? options.action : this.action
            this.header = options.header != undefined ? options.header : this.header
            this.method = options.method != undefined ? options.method : this.method
            this.modalType = options.type != undefined ? options.type : 'slideout'
            this.url = options.url != undefined ? options.url : this.url
            this.redirect = options.redirect != undefined ? options.redirect : this.redirect
            this.callback = options.callback != undefined ? options.callback : this.callback

            this.buttons = options.buttons != undefined ? options.buttons : []
        },
        closeModal: function() {
            console.log('closeModal')
            this.show = false
            setTimeout(function() {
                this.showWrapper = false
                this.hideContent()
            }.bind(this), 500)
        },
        hideContent() {
            this.$refs.contents.innerHtml = ''
        },
        showContent(element) {
            if (element) {
                this.$refs.contents.appendChild(element)
            }
        },
        loadContent() {
            let _this = this;
            this.loadUrl(this.url, 'div', function(res, err) {
                _this.showContent(res);
            })
        },
        loadUrl(url, selector, callback) {
            if (typeof url !== 'string') {
                throw new Error('Invalid URL: ', url);
            } else if (typeof selector !== 'string') {
                throw new Error('Invalid selector selector: ', selector);
            } else if (typeof callback !== 'function') {
                throw new Error('Callback provided is not a function: ', callback);
            }

            var xhr = new XMLHttpRequest();
            var finished = false;
            xhr.onabort = xhr.onerror = function xhrError() {
                finished = true;
                callback(null, xhr.statusText);
            };

            xhr.onreadystatechange = function xhrStateChange() {
                if (xhr.readyState === 4 && !finished) {
                    finished = true;
                    var section;
                    try {
                        section = xhr.responseXML.querySelector(selector);
                        console.log('selector', section);
                        callback(section);
                    } catch (e) {
                        callback(null, e);
                    }
                }
            };

            xhr.open('GET', url);
            xhr.responseType = 'document';
            xhr.send();
        },
        buttonClass(button) {
            if (button && button.class != undefined) {
                return button.class
            }

            return {}
        },
        buttonClick(button, $event) {
            if (button && button.prevent != undefined && button.prevent) {
                $event.preventDefault();
            }

            if (button && button.close != undefined && button.close) {
                this.closeModal();
            }
        },
        modalEffects: {
            ['x-transition:enter']() {
                this.hideContent()
                this.loadContent()
                console.log('x-transition:enter')
                if (this.modalType == 'slideout') {
                    return 'transform transition ease-in-out duration-500 sm:duration-700';
                }
                return 'transition ease-out duration-300';
            },
            ['x-transition:enter-start']() {
                console.log('x-transition:enter-start')
                if (this.modalType == 'slideout') {
                    return 'translate-x-full';
                }
                return 'opacity-0';
            },
            ['x-transition:enter-end']() {
                console.log('x-transition:enter-end')
                if (this.modalType == 'slideout') {
                    return 'translate-x-0';
                }
                return 'opacity-100';
            },
            ['x-transition:leave']() {
                console.log('x-transition:leave')
                if (this.modalType == 'slideout') {
                    return 'transform transition ease-in-out duration-500 sm:duration-700';
                }
                return 'transition ease-out duration-300';
            },
            ['x-transition:leave-start']() {
                console.log('x-transition:leave-start')
                if (this.modalType == 'slideout') {
                    return 'translate-x-0';
                }
                return 'opacity-100';
            },
            ['x-transition:leave-end']() {
                console.log('x-transition:leave-end')
                if (this.modalType == 'slideout') {
                    return 'translate-x-full';
                }
                return 'opacity-0';
            },
        }
    }
};

window.modalButton = function(options = { type: 'centered' }) {
    return {
        open: function($dispatch) {
            $dispatch('openmodal', options);
        }
    }
};