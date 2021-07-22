describe('Articles', () => {
    it("should pass the audits", function () {
        cy.visit('/articles')
        cy.runAudit()
    })
})

describe('Articles → Article', () => {
    it("should pass the audits", function () {
        cy.visit('/articles')

        // Click the first article
        cy.get('a.article-card').first()
            .click()

        cy.runAudit()
    })
})
