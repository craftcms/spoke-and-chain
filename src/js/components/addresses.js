window.addresses = function() {
    return {
        countryId: null,
        stateId: null,
        modelName: null,
        stateSelectId: null,
        stateTextId: null,
        showStateSelect: false,
        allStates() {
            return window.addressStates;
        },
        states() {
            if (this.countryId && Object.keys(this.allStates()).indexOf(this.countryId) >= 0) {
                let states = [];
                Object.keys(this.allStates()[this.countryId]).forEach(key => {
                    states.push({
                        id: key,
                        name: this.allStates()[this.countryId][key]
                    });
                });

                return states;
            }

            return [];
        },
        toggleStates() {

            if (this.states().length) {
                this.stateSelectId = this.modelName + '-state';
                this.stateTextId = '';
                this.showStateSelect = true;
            } else {
                this.stateSelectId = '';
                this.stateTextId = this.modelName + '-state';
                this.showStateSelect = false;
            }

        },
        onChange(ev) {
            this.countryId = ev.target.value;
            this.toggleStates();
        }
    };
};