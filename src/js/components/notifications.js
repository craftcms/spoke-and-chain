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
            ['x-transition:leave']() {
                return 'transition ease-out duration-300';
            },
            ['x-transition:leave-start']() {
                return 'opacity-100';
            },
            ['x-transition:leave-end']() {
                return 'opacity-0';
            },
        }
    }
};
