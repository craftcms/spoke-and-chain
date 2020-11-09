window.quickReplace = function() {
    if (window.quickReplaceUri != undefined) {
        history.replaceState({}, '' , window.quickReplaceUri);
    }
}

window.quickReplace();