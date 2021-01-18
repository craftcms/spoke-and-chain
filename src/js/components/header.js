window.header = function() {
    return {
        // Nav
        showNav: false,
        toggleNav() {
            this.showNav = !this.showNav

            const $html = document.querySelector('html')
            const $body = document.querySelector('body')

            if (this.showNav) {
                $html.classList.add('overflow-y-scroll', 'lg:overflow-y-auto', 'h-full', 'lg:h-auto')
                $body.classList.add('overflow-y-hidden', 'lg:overflow-y-auto', 'h-full', 'lg:h-auto')
            } else {
                $html.classList.remove('overflow-y-scroll', 'lg:overflow-y-auto', 'h-full', 'lg:h-auto')
                $body.classList.remove('overflow-y-hidden', 'lg:overflow-y-auto', 'h-full', 'lg:h-auto')
            }
        },

        // Search
        showSearch: false,
        openSearch($nextTick) {
            this.showSearch = true
            $nextTick(() => {
                const $searchInput = document.querySelector('#search-input')

                // Put the focus on the search input
                $searchInput.focus()

                // Put the cursor at the end of the text
                $searchInput.selectionStart = $searchInput.selectionEnd = $searchInput.value.length;
            })
        },
        closeSearch() {
            this.showSearch = false
        },

        // Cart
        showCartMenu: false,
    }
}