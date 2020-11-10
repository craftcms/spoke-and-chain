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
        toleranceDistance: 50,

        $header: document.querySelector('#header'),
        $mainNav: document.querySelector('#main-nav'),

        init() {
            let _this = this
            this.updateMainNavHeight()

            const ro = new ResizeObserver(entries => {
                for (let entry of entries) {
                    this.updateMainNavHeight();
                }
            });

            ro.observe(this.$header);
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
            // added pixels for tolerance
            return this.lastScrollValue > 0 && this.lastScrollValue > (this.mainNavHeight + this.toleranceDistance)
        },

        updateMainNavHeight() {
            this.mainNavHeight = this.$mainNav.offsetHeight
            this.$header.style.height = this.mainNavHeight + 'px'
        }
    }
}