window.notifications = function() {
    return {
        show: false,
        init: function(type) {
            setTimeout(function() {
                this.show = true

                setTimeout(function() {
                    this.show = false
                }.bind(this), 3000 * (type === 'error' ? 2 : 1))
            }.bind(this), 50)
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
