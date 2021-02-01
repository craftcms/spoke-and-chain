describe('Services', () => {
    it(`should show two plans`, function () {
        cy.visit('/services')

        cy.get('div.services-plan')
            .its('length')
            .should('be.eq', 2)
    })
})
