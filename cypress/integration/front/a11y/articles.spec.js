describe('Articles', () => {
    it("should pass the audits", function () {
        cy.visit('/articles')
        cy.runAudit()
    })
})
