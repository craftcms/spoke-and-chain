describe('Homepage', () => {
    it("should pass the audits", function () {
        cy.visit('/')
        cy.runAudit()
    })
})
