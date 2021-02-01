describe('Homepage', () => {
    it(`should show an “All bikes” button`, function () {
        cy.visit('/')

        cy.get('a.button')
            .contains('Browse all bikes')
    })

    it(`should show bike categories`, function () {
        cy.visit('/')

        cy.get('a.category-card')
            .its('length')
            .should('be.gt', 0)
    })
})
