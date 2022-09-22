describe('Services', () => {
    it("should pass the audits", function () {
        cy.visit('/services')
        cy.runAudit()
    })
})
