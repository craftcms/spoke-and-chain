window.header = function() {
    return {
        showNav: false,
        showCartMenu: false,
        openCartMenu() {
            this.showCartMenu = true
        },
        closeCartMenu() {
            this.showCartMenu = false
        }
    }
}