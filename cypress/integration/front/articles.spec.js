const sizes = require('../../viewport-sizes')

sizes.forEach((size) => {
    describe(`Services on ${size} screen`, () => {
        beforeEach(function() {
            cy.setViewportSize(size)
        })

        it(`should show two plans`, function () {
            cy.visit('/articles')

            cy.get('a.article-card')
                .its('length')
                .should('be.gt', 0)
        })
    })
})
