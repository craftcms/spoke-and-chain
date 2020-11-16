window.sort = function() {
    return {
        url: null,
        onChange($ev, $refs) {
            console.log($refs.sort.value);
            let newUrl = this.url.replace('sort=sort', $refs.sort.value ? 'sort=' + $refs.sort.value : '');
            console.log(newUrl.slice(-1, 1));
            if (newUrl.slice(-1) == '?' || newUrl.slice(-1) == '&') {
                newUrl = newUrl.slice(0, newUrl.length - 1);
            }

            window.location = newUrl;
        }
    };
};