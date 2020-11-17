window.modal = function() {
    return {
        action: null, // action string for the form
        buttons: [], // buttons to display format: {}
        callback: null, // buttons to display format: {}
        contentLoaded: false,
        errorKey: null,
        form: false, // whether or not to output the form
        header: null, // header text
        method: 'post',
        modalType: 'centered', // modal type 'centered' or 'slideout'
        redirect: null,
        show: false,
        showWrapper: false,
        submitting: false,
        success: false,
        url: null, // HTML content for the modal

        openModal: function(options) {
            this.show = true
            this.showWrapper = true

            this.action = options.action != undefined ? options.action : this.action
            this.buttons = options.buttons != undefined ? options.buttons : []
            this.callback = options.callback != undefined ? options.callback : this.callback
            this.errorKey = options.errorKey != undefined ? options.errorKey : this.errorKey
            this.header = options.header != undefined ? options.header : this.header
            this.method = options.method != undefined ? options.method : this.method
            this.modalType = options.type != undefined ? options.type : 'slideout'
            this.redirect = options.redirect != undefined ? options.redirect : this.redirect
            this.url = options.url != undefined ? options.url : this.url
        },
        closeModal: function() {
            this.show = false
            setTimeout(function() {
                this.showWrapper = false
                this.hideContent()
            }.bind(this), 500)
        },
        hideContent() {
            this.contentLoaded = false
        },
        showContent(element) {
            if (element) {
                this.$refs.contents.appendChild(element)
                this.contentLoaded = true
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

            fetch(url)
                .then(response => response.text())
                .then(text => {
                    let parser = new DOMParser();
                    let htmlDoc = parser.parseFromString(text, 'text/html');
                    let content = htmlDoc.documentElement.querySelector(selector);

                    callback(content);
                }).catch(err => {
                    callback(null, err);
            });
        },
        submit($event, $dispatch) {
            this.submitting = true;

            let $form = $event.target;
            var data = new FormData($form);

            fetch('/', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: data,
            })
                .then(response => response.json())
                .then(result => {
                    this.submitting = false;

                    if (result.success == undefined) {
                        if (result.error && this.errorKey && result.errors != undefined) {
                            $dispatch(this.errorKey, result.errors);
                        }
                    } else if (result.success) {
                        this.success = true;
                        window.location = this.redirect;
                    }
                })
                .catch(error => {
                    this.submitting = false;
                    alert(result.message);
                    console.error('Error:', error);
                });
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

            if (button && button.type != undefined && button.type == 'close') {
                this.closeModal();
            }
        },
        modalEffects: {
            ['x-transition:enter']() {
                this.hideContent()
                this.loadContent()
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

window.modalButton = function(options = { type: 'centered' }) {
    return {
        open: function($dispatch) {
            $dispatch('openmodal', options);
        }
    }
};