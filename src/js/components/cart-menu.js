window.cartMenu = function() {
    return {
        show: false,
        open() {
            this.show = true
        },
        close() {
            this.show = false
        }
    }
}