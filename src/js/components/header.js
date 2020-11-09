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
        scrollDirection: 'down',

        init() {
            this.updateMainNavHeight()
        },

        onScroll() {
            this.updateMainNavHeight()

            const scrollValue = window.scrollY

            if (scrollValue > this.lastScrollValue) {
                this.scrollDirection = 'down'
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
                this.scrollDirection = 'up'
                this.mainNavVisible = true
            }

            this.lastScrollValue = scrollValue
        },

        hasScrolledPastHeader() {
            // +10 for tolerance
            return this.lastScrollValue > 0 && this.lastScrollValue > (this.mainNavHeight + 10)
        },

        updateMainNavHeight() {
            const $mainNav = document.getElementById('main-nav');
            const $header = document.getElementById('header');

            this.mainNavHeight = $mainNav.offsetHeight
            $header.style.height = this.mainNavHeight + 'px'
        }
    }
}