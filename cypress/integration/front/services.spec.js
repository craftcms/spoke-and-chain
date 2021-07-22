const sizes = require('../../viewport-sizes')

sizes.forEach((size) => {
    describe(`Services on ${size} screen`, () => {
        beforeEach(function() {
            cy.setViewportSize(size)
        })

        it(`should show two plans`, function () {
            cy.visit('/services')

            cy.get('div.services-plan')
                .its('length')
                .should('be.eq', 2)
        })
    })
})
