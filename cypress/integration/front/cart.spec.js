describe('Cart', () => {
    it(`should be able to add a product to the cart`, function () {
        cy.addProductToCart()
        cy.navigateToCart()

        cy.get('div.line-item')
            .its('length')
            .should('be.gt', 0)
    })
})
