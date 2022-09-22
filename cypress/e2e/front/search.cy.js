const sizes = require('../../viewport-sizes')

const searchQuery = 'rift zone'

sizes.forEach((size) => {
    describe(`Search on ${size} screen`, () => {
        beforeEach(function() {
            cy.setViewportSize(size)
        })

        it(`should show search results`, function() {
            cy.visit('/')

            let $input, $form

            if (size === 'iphone-6' || (Array.isArray(size) && size[0] < 1024)) {
                cy.get('#header button.toggle-nav')
                    .click()
                $input = cy.get('.search-form input#search-input')
                $form = cy.get('form.search-form')
            } else {
                cy.get('#header button.search-toggle')
                    .click()
                $input = cy.get('#search-form input#search-input')
                $form = cy.get('form#search-form');
            }

            $input.type(searchQuery)
            $form.submit()

            cy.get('h1').contains(`Search results for “${searchQuery}”`)

            cy.get('a.product-card')
                .its('length')
                .should('be.gt', 0)
        })
    })
})
