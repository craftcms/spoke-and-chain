window.modal = function() {
    return {
        showWrapper: false,
        show: false,
        modalType: 'centered',
        openModal: function(modalType) {
            console.log('openModal', modalType)
            this.show = true
            this.showWrapper = true
            this.modalType = modalType
        },
        closeModal: function() {
            console.log('closeModal')
            this.show = false
            setTimeout(function() {
                this.showWrapper = false
            }.bind(this), 500)
        },
        modalEffects: {
            ['x-transition:enter']() {
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

window.modalButton = function(type = 'centered') {
    return {
        foo: 'bar',
        open: function($dispatch) {
            $dispatch('openmodal', { modalType: type });
        }
    }
};