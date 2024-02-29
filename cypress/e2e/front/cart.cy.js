const sizes = require('../../viewport-sizes')

sizes.forEach((size) => {
    describe(`Cart on ${size} screen`, () => {
        beforeEach(function() {
            cy.setViewportSize(size)
        })

        it(`should be able to add a product to the cart`, function () {
            cy.addProductToCart()
            cy.navigateToCart()

            // Make sure that the cart contains at least one item
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

        it(`should be able to apply a discount code`, function () {
            cy.addProductToCart()
            cy.navigateToCart()

            // Click link to enter a coupon
            cy.get('form#cart a.coupon-trigger')
                .click()

            // Use the `15OFF` coupon code
            cy.get('form#cart input[name=couponCode]')
                .type('15OFF')

            // Apply the coupon
            cy.get('form#cart button[type=submit].coupon-apply')
                .click()

            // Check that the cart contains a discount row
            cy.get('form#cart div.discount-row')
                .its('length')
                .should('be.gt', 0)
        })
    })
})
