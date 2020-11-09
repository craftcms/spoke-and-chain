window.header = function() {
    return {
        showNav: false,
        showCartMenu: false,
        openCartMenu() {
            this.showCartMenu = true
        },
        closeCartMenu() {
            this.showCartMenu = false
        },

        lastScrollValue: 0,
        mainNavVisible: true,
        mainNavFixed: true,
        mainNavHeight: 0,

        init() {
            this.updateMainNavHeight()
        },

        onScroll() {
            this.updateMainNavHeight()

            const scrollValue = window.scrollY

            if (scrollValue > this.lastScrollValue) {
                // Scroll down
                this.mainNavVisible = false

                if (scrollValue > this.mainNavHeight) {
                    this.mainNavFixed = true
                } else {
                    this.mainNavFixed = false
                    this.mainNavVisible = true
                }
            } else {
                // Scroll up
                this.mainNavVisible = true
            }

            this.lastScrollValue = scrollValue
        },

        updateMainNavHeight() {
            const $mainNav = document.getElementById('main-nav');
            const $header = document.getElementById('header');

            this.mainNavHeight = $mainNav.offsetHeight
            $header.style.height = this.mainNavHeight + 'px'
        }
    }
}