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
        saveState: true,
        focusElement: null,

        init(state, $nextTick) {
            let _this = this;
            this._nt = $nextTick;
            this.setFromState(state);

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

            htmx.on('htmx:afterSwap', function(event) {
                if (event.detail.target.getAttribute('id') == 'filter') {
                    _this._nt(function() {
                        var el = _this.focusElement ? document.querySelector('#' + _this.focusElement) : null;
                        // Re-focus the element that made the call.
                        if (el) {
                            el.focus();
                            _this.focusElement = null;
                        }
                    });
                }
            });
        },

        setFromState(state) {
            this.type = state.type;
            this.colors = state.colors;
            this.materials = state.materials;
            this.sort = state.sort;
        },

        toggle(key, value, $event) {
            var idx = this[key].indexOf(value);
            if (idx == -1) {
                this[key].push(value);
            } else {
                if (this[key].length == 1) {
                    this[key] = [];
                } else {
                    delete this[key][idx];
                }
            }

            this.refresh();
        },

        clear() {
            this.type = '';
            this.colors = [];
            this.materials = [];
            this.sort = '';

            this.refresh();
        },

        setType(val, $event) {
            var el = $event.target || $event.srcElement;

            this.focusElement = el.id;
            this.type = val == this.type ? '' : val;

            this.refresh();
        },

        refresh() {
            var _this = this;
            // always hide mobile filter list when selecting a filter
            this.showFilters = false;

            this._nt(function() {
                htmx.trigger(htmx.find('#filter'), 'refresh');
            });
        }
    };
};