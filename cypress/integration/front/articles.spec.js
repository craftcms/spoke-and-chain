describe('Services', () => {
    it(`should show two plans`, function () {
        cy.visit('/articles')

        cy.get('a.article-card')
            .its('length')
            .should('be.gt', 0)
    })
})
