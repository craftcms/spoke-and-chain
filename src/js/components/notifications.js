window.notifications = function() {
    return {
        duration: 3000,
        noticeVisible: false,
        errorVisible: false,
        init: function() {
            this.$nextTick(() => {
                this.noticeVisible = true
                this.errorVisible = true

                setTimeout(function() {
                    this.noticeVisible = false
                }.bind(this), this.duration)

                setTimeout(function() {
                    this.errorVisible = false
                }.bind(this), this.duration * 2)
            })
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
