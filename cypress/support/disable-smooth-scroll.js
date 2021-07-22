Cypress.on('window:before:load', win => {
    const htmlNode = win.document.querySelector('html')
    htmlNode.classList.remove('smooth-scroll')
})