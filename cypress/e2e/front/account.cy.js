const sizes = require('../../viewport-sizes')

sizes.forEach((size) => {
    describe(`Account on ${size} screen`, () => {
        beforeEach(function() {
            cy.setViewportSize(size)
            cy.login()
        })

        it(`should contain an Orders section`, function() {
            cy.visit('/')

            cy.get('#header .cart-toggle')
                .click()

            cy.get('#header .cart-menu a')
                .contains('Orders')
                .click()

            cy.get('h1')
                .contains('Orders')
        })

        it(`should contain an Membership section`, function() {
            cy.visit('/')

            cy.get('#header .cart-toggle')
                .click()

            cy.get('#header .cart-menu a')
                .contains('Membership')
                .click()

            cy.get('h1')
                .contains('Membership')
        })

        it(`should contain an Settings section`, function() {
            cy.visit('/')

            cy.get('#header .cart-toggle')
                .click()

            cy.get('#header .cart-menu a')
                .contains('Account')
                .click()

            cy.get('h1')
                .contains('Account')
        })
    })
})
