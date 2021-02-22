window.header = function() {
    return {
        // Nav
        showNav: false,
        toggleNav() {
            this.showNav = !this.showNav

            const $html = document.querySelector('html')
            const $body = document.querySelector('body')

            if (this.showNav) {
                $html.classList.add('showing-nav')
                $body.classList.add('showing-nav')
            } else {
                $html.classList.remove('showing-nav')
                $body.classList.remove('showing-nav')
            }
        },

        // Search
        showSearch: false,
        searchFocused: false,
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
        onSearchFocus() {
            this.searchFocused = true
        },
        onSearchBlur() {
            this.searchFocused = false
        },

        // Cart
        showCartMenu: false,
    }
}