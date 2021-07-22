describe('Contact', () => {
    it("should pass the audits", function () {
        cy.visit('/contact')
        cy.runAudit()
    })
})
