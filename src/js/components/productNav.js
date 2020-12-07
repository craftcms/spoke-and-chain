window.productNav = function() {
    return {
        productNavVisible: false,

        onScroll() {
            if (this.productNavVisible) {
                this.productNavVisible = false
            }
        },
    }
}