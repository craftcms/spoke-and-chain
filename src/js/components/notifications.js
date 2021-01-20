window.notifications = function() {
    return {
        duration: 3000,
        visible: false,
        type: 'notice',
        message: null,

        show: function(options) {
            if (options.type) {
                this.type = options.type;
            }

            if (options.message) {
                this.message = options.message;
            }

            this.visible = true;

            setTimeout(function() {
                this.visible = false;
            }.bind(this), this.duration);
        },

        effects: {
            ['x-transition:enter']() {
                return 'transition transform ease-out duration-300';
            },
            ['x-transition:enter-start']() {
                return '-translate-y-full';
            },
            ['x-transition:enter-end']() {
                return 'translate-y-0';
            },
            ['x-transition:leave']() {
                return 'transition transform ease-out duration-300';
            },
            ['x-transition:leave-start']() {
                return 'translate-y-0';
            },
            ['x-transition:leave-end']() {
                return '-translate-y-full';
            },
        }
    }
};

window.addNotification = function(type = 'notice', message = null) {
    if (type && message) {
        window.dispatchEvent(new CustomEvent('notification', {
            detail: {
                type: type,
                message: message
            }
        }));
    }
};
