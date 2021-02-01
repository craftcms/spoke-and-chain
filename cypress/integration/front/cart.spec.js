describe('Cart', () => {
    it(`should be able to add a product to the cart`, function () {
        cy.addProductToCart()
        cy.navigateToCart()

        cy.get('div.line-item')
            .its('length')
            .should('be.gt', 0)
    })

    it(`should be able to remove a product from the cart`, function () {
        cy.addProductToCart()
        cy.navigateToCart()

        cy.get('div.line-item').then(($el) => {
            // Retrieve line item ID
            const lineItemId = $el.attr('id').substr(10)

            // Make sure the the line item exists
            cy.get('#line-item-' + lineItemId).should('exist')

            // Remove the line item
            cy.get('#remove-trigger-' + lineItemId)
                .click()

            // Check that the line item has properly been removed
            cy.get('#line-item-' + lineItemId).should('not.exist')
        })
    })
})
