window.filterPreviousState = {
    type: '',
    colors: [],
    materials: [],
    sort: ''
};
window.filterPreviousStates = [];

window.filter = function() {
    return {
        showFilters: false,
        type: '',
        colors: [],
        materials: [],
        sort: '',
        _nt: null,
        state: {},
        filterPreviousState: [],
        saveState: true,

        init($nextTick) {
            let _this = this;
            this._nt = $nextTick;

            // Using window variables to avoid alpine proxying all the data
            window.addEventListener('saveFilterState', function(ev) {
                window.filterPreviousStates.push(window.filterPreviousState);
                window.filterPreviousState = ev.detail;
            });

            window.addEventListener('popstate', function(e) {
                if (window.filterPreviousStates.length) {
                    var filterPreviousState = window.filterPreviousStates.pop();
                    e.preventDefault();
                    _this.setFromState(filterPreviousState);
                    _this.saveState = false;
                    _this.refresh();
                }
            });
        },

        setFromState(state) {
            this.type = state.type;
            this.colors = state.colors;
            this.materials = state.materials;
            this.sort = state.sort;
        },

        toggle(key, value) {
            var idx = this[key].indexOf(value);
            if (idx == -1) {
                this[key].push(value);
            } else {
                delete this[key][idx];
            }

            this.refresh();
        },

        setType(val) {
            this.type = val == this.type ? '' : val;

            this.refresh();
        },

        refresh() {
            this._nt(function() { htmx.trigger(htmx.find('#filter'), 'refresh')});
        }
    };
};